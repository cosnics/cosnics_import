<?php
namespace Chamilo\Application\Weblcms\Tool\Implementation\Document\Component;

use Chamilo\Application\Weblcms\Rights\WeblcmsRights;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublication;
use Chamilo\Application\Weblcms\Storage\DataClass\ContentObjectPublicationCategory;
use Chamilo\Application\Weblcms\Storage\DataManager as WeblcmsDataManager;
use Chamilo\Application\Weblcms\Tool\Implementation\Document\Manager;
use Chamilo\Core\Repository\ContentObject\File\Storage\DataClass\File;
use Chamilo\Core\Repository\ContentObject\Webpage\Storage\DataClass\Webpage;
use Chamilo\Core\Repository\Storage\DataClass\ContentObject;
use Chamilo\Core\Repository\Storage\DataManager;
use Chamilo\Libraries\Architecture\Exceptions\UserException;
use Chamilo\Libraries\File\Compression\ZipArchive\ZipArchiveFilecompression;
use Chamilo\Libraries\Format\Structure\BreadcrumbTrail;
use Chamilo\Libraries\Storage\Parameters\DataClassRetrievesParameters;
use Chamilo\Libraries\Storage\Query\Condition\AndCondition;
use Chamilo\Libraries\Storage\Query\Condition\EqualityCondition;
use Chamilo\Libraries\Storage\Query\Condition\InCondition;
use Chamilo\Libraries\Storage\Query\Condition\SubselectCondition;
use Chamilo\Libraries\Storage\Query\Variable\PropertyConditionVariable;
use Chamilo\Libraries\Storage\Query\Variable\StaticConditionVariable;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @package application.lib.weblcms.tool.document.component
 */
class ZipAndDownloadComponent extends Manager
{

    private $zip_name;

    public function run()
    {
        $archivePath = $this->create_document_archive();
        $archiveName = $this->zip_name . '.zip';
        $archiveSafeName =
            StringUtilities::getInstance()->createString($archiveName)->toAscii()->replace('/', '-')->replace(
                '\\', '-'
            )->replace('%', '_')->__toString();

        $response = new BinaryFileResponse($archivePath, 200, ['Content-Type' => 'application/zip']);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $archiveSafeName);
        $response->prepare($this->getRequest());
        $response->send();

        $this->getFilesystem()->remove($archivePath);
    }

    /**
     * @param BreadcrumbTrail $breadcrumbtrail
     */
    public function addAdditionalBreadcrumbs(BreadcrumbTrail $breadcrumbtrail): void
    {
        $this->addBrowserBreadcrumb($breadcrumbtrail);
    }

    private function create_document_archive()
    {
        $filesystem = $this->getFilesystem();
        $count = 0;

        $course_name = $this->get_course()->get_title();
        $this->zip_name = $course_name . ' - ' . Translation::get('Document');

        $category_id = $this->getRequest()->getFromRequestOrQuery(\Chamilo\Application\Weblcms\Manager::PARAM_CATEGORY);
        if (!isset($category_id) || $category_id == 0 || strlen($category_id) == 0)
        {
            $category_id = 0;
        }
        else
        {
            $category = WeblcmsDataManager::retrieve_by_id(
                ContentObjectPublicationCategory::class, $category_id
            );

            $this->zip_name .= ' -_' . $category->get_name();
        }

        $is_course_admin = $this->get_course()->is_course_admin($this->get_user());

        // needed to prevent cutoff at a space char
        $this->zip_name = str_replace(' ', '_', $this->zip_name);

        $category_folder_mapping = $this->create_folder_structure($category_id, $is_course_admin);

        $target_path = current($category_folder_mapping);
        foreach ($category_folder_mapping as $category_id => $dir)
        {
            // if we have access, retrieve the publications in the current
            // category

            if ($is_course_admin)
            {
                $conditions = [];

                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectPublication::class, ContentObjectPublication::PROPERTY_COURSE_ID
                    ), new StaticConditionVariable($this->get_course_id())
                );

                $conditions[] = new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectPublication::class, ContentObjectPublication::PROPERTY_TOOL
                    ), new StaticConditionVariable($this->get_tool_id())
                );

                $conditions[] = new InCondition(
                    new PropertyConditionVariable(
                        ContentObjectPublication::class, ContentObjectPublication::PROPERTY_CATEGORY_ID
                    ), $category_id
                );

                $subselect_condition = new InCondition(
                    new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_TYPE),
                    [File::class, Webpage::class]
                );

                $conditions[] = new SubselectCondition(
                    new PropertyConditionVariable(
                        ContentObjectPublication::class, ContentObjectPublication::PROPERTY_CONTENT_OBJECT_ID
                    ), new PropertyConditionVariable(ContentObject::class, ContentObject::PROPERTY_ID),
                    $subselect_condition
                );

                $condition = new AndCondition($conditions);

                $publications = WeblcmsDataManager::retrieve_content_object_publications(
                    $condition
                );
            }
            else
            {
                if ($category_id == 0)
                {
                    $course_module_id = $this->get_tool_registration()->get_id();
                    $location_id =
                        WeblcmsRights::getInstance()->get_weblcms_location_by_identifier_from_courses_subtree(
                            WeblcmsRights::TYPE_COURSE_MODULE, $course_module_id, $this->get_course_id()
                        );
                }
                else
                {
                    $location_id =
                        WeblcmsRights::getInstance()->get_weblcms_location_by_identifier_from_courses_subtree(
                            WeblcmsRights::TYPE_COURSE_CATEGORY, $category_id, $this->get_course_id()
                        );
                }

                // Only retrieve visible publications
                $condition = new EqualityCondition(
                    new PropertyConditionVariable(
                        ContentObjectPublication::class, ContentObjectPublication::PROPERTY_HIDDEN
                    ), new StaticConditionVariable(0)
                );

                $publications =
                    WeblcmsDataManager::retrieve_content_object_publications_with_view_right_granted_in_category_location(
                        $location_id, $this->get_entities(), null, [], 0, - 1, $this->get_user_id()
                    );
            }

            if ($publications)
            {
                foreach ($publications as $publication)
                {
                    if (!$is_course_admin && $publication[ContentObjectPublication::PROPERTY_HIDDEN])
                    {
                        if (!$this->is_allowed(WeblcmsRights::EDIT_RIGHT, $publication))
                        {
                            continue;
                        }
                    }

                    $count ++;

                    $document = DataManager::retrieve_by_id(
                        ContentObject::class, $publication[ContentObjectPublication::PROPERTY_CONTENT_OBJECT_ID]
                    );

                    if (!$document instanceof File && !$document instanceof Webpage)
                    {
                        continue;
                    }

                    $document_path = $document->get_full_path();
                    $archive_file_location = $dir . '/' . $this->getFilesystemTools()->createUniqueName(
                            $dir, $document->get_filename()
                        );
                    $filesystem->copy($document_path, $archive_file_location);
                }
            }
        }

        if ($count == 0)
        {
            throw new UserException(Translation::get('NoDocumentsPublished'));
        }

        $compression = $this->getZipArchiveFilecompression();
        $archiveFile = $compression->createArchive($target_path);

        $filesystem->remove($target_path);

        return $archiveFile;
    }

    /**
     * Creates a folder structure from the given categories.
     *
     * @param $categories              array|int
     * @param $category_folder_mapping array
     * @param $path
     *
     * @return array An array mapping the category id to the folder.
     */
    private function create_folder_structure(
        $parent_cat, $course_admin = false, &$category_folder_mapping = [], $path = null
    )
    {
        $filesystem = $this->getFilesystem();
        $filesystemTools = $this->getFilesystemTools();

        if (is_null($path))
        {
            $path = $this->getConfigurablePathBuilder()->getTemporaryPath(__NAMESPACE__);
            $path = $filesystemTools->createUniqueName($path . 'weblcms_document_download_' . $this->get_course_id());
            $category_folder_mapping[$parent_cat] = $path;
            $filesystem->mkdir($path);
        }

        $conditions = [];

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_COURSE
            ), new StaticConditionVariable($this->get_course_id())
        );

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_TOOL
            ), new StaticConditionVariable($this->get_tool_id())
        );

        $conditions[] = new EqualityCondition(
            new PropertyConditionVariable(
                ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_PARENT
            ), new StaticConditionVariable($parent_cat)
        );

        if (!$course_admin)
        {
            $conditions[] = new EqualityCondition(
                new PropertyConditionVariable(
                    ContentObjectPublicationCategory::class, ContentObjectPublicationCategory::PROPERTY_VISIBLE
                ), new StaticConditionVariable(true)
            );
        }

        $condition = new AndCondition($conditions);

        $categories = WeblcmsDataManager::retrieves(
            ContentObjectPublicationCategory::class, new DataClassRetrievesParameters($condition)
        );

        foreach ($categories as $category)
        {
            // Make safe name
            $safe_name = strtr(
                $category->get_name(), 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïðñòóôõöøùúûüýÿ',
                'aaaaaaaceeeeiiiidnoooooouuuuyaaaaaaceeeeiiiidnoooooouuuuyy'
            );
            $safe_name = preg_replace('/[^0-9a-zA-Z\-\s\(\),]/', '_', $safe_name);

            $category_path = $filesystemTools->createUniqueName($path . '/' . $safe_name);
            $category_folder_mapping[$category->getId()] = $category_path;
            $filesystem->mkdir($category_path);
            $this->create_folder_structure(
                $category->getId(), $course_admin, $category_folder_mapping, $category_path
            );
        }

        return $category_folder_mapping;
    }

    protected function getZipArchiveFilecompression(): ZipArchiveFilecompression
    {
        return $this->getService(ZipArchiveFilecompression::class);
    }
}
