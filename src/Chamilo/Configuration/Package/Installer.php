<?php
namespace Chamilo\Configuration\Package;

use Chamilo\Configuration\Storage\DataClass\Language;
use Chamilo\Libraries\Translation\Translation;
use Chamilo\Libraries\Utilities\StringUtilities;
use DOMDocument;
use DOMXPath;
use Symfony\Component\Finder\Iterator\FileTypeFilterIterator;

/**
 * @package admin.install
 */

/**
 * This installer can be used to create the storage structure for the users application.
 */
class Installer extends Action\Installer
{
    public const CONTEXT = 'Chamilo\Configuration';

    public function create_languages()
    {
        $language_path = $this->getSystemPathBuilder()->getI18nPath();
        $language_files =
            $this->getFilesystemTools()->getDirectoryContent($language_path, FileTypeFilterIterator::ONLY_FILES, false);

        foreach ($language_files as $language_file)
        {
            $file_info = pathinfo($language_file);
            $language_info_file = $language_path . $file_info['filename'] . '.info';

            if (file_exists($language_info_file) && $file_info['extension'] == 'info')
            {
                $dom_document = new DOMDocument('1.0', 'UTF-8');
                $dom_document->load($language_info_file);
                $dom_xpath = new DOMXPath($dom_document);

                $language_node = $dom_xpath->query('/packages/package')->item(0);

                $language = new Language();
                $language->set_original_name($dom_xpath->query('name', $language_node)->item(0)->nodeValue);
                $language->set_english_name($dom_xpath->query('extra/english', $language_node)->item(0)->nodeValue);
                $language->set_family($dom_xpath->query('category', $language_node)->item(0)->nodeValue);
                $language->set_isocode($dom_xpath->query('extra/isocode', $language_node)->item(0)->nodeValue);
                $language->set_available('1');

                if ($language->create())
                {
                    $this->add_message(
                        self::TYPE_NORMAL, Translation::get(
                            'ObjectAdded', ['OBJECT' => Translation::get('Language')], StringUtilities::LIBRARIES
                        ) . ' ' . $language->get_english_name()
                    );
                }
                else
                {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Runs the install-script.
     */
    public function extra(): bool
    {

        // Add the default language entries in the database
        if (!$this->create_languages())
        {
            return false;
        }
        else
        {
            $this->add_message(
                self::TYPE_NORMAL, Translation::get(
                'ObjectsAdded', ['OBJECTS' => Translation::get('Languages')], StringUtilities::LIBRARIES
            )
            );
        }

        return true;
    }
}
