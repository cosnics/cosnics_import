import axios from 'axios';
import APIConfig from './APIConfig';
import PQueue from 'p-queue';
import {Category, ColumnId, GradeColumn, GradeItem, ItemId} from '../domain/GradeBook';

const HTTP_FORBIDDEN = 403;
const HTTP_NOT_FOUND = 404;
const HTTP_CONFLICT = 409;
const ERROR_UNKNOWN = 'UNKNOWN';

const TIMEOUT_SEC = 30;

function timeout(ms: number) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

export interface ConnectorErrorListener {
    setError(data: any) : void;
}

export default class Connector {
    private apiConfig: APIConfig;
    private queue = new PQueue({concurrency: 1});

    private gradebookDataId: number;
    private currentVersion: number|null;

    private _isSaving = false;
    private errorListeners: ConnectorErrorListener[] = [];

    constructor(apiConfig: APIConfig, gradebookDataId: number, currentVersion: number|null) {
        this.apiConfig = apiConfig;
        this.gradebookDataId = gradebookDataId;
        this.currentVersion = currentVersion;

        this.finishSaving = this.finishSaving.bind(this);
    }

    addErrorListener(errorListener: ConnectorErrorListener) {
        this.errorListeners.push(errorListener);
    }

    removeErrorListener(errorListener: ConnectorErrorListener) {
        const index = this.errorListeners.indexOf(errorListener);
        if (index >= 0) {
            this.errorListeners.splice(index, 1);
        }
    }

    get processingSize() {
        return this.queue.pending + this.queue.size;
    }

    get isSaving() {
        return this._isSaving;
    }

    private beginSaving() {
        this._isSaving = true;
    }

    private finishSaving(): void {
        this._isSaving = false;
    }

    static async loadGradeBookData(loadAllURL: string, csrfToken: string|undefined) {
        const params = csrfToken ? {'_csrf_token': csrfToken } : {};
        const res = await axios.get(loadAllURL, {params});
        return res.data;
    }

    async loadGradeItemScores(gradeItem: GradeItem) {
        const res = await axios.get(this.apiConfig.loadGradeItemScoresURL, {params: {'gradeItemId': gradeItem.id}});
        this.logResponse(res.data);
        return res.data;
    }

    addCategory(category: Category) {
        this.addToQueue(async () => {
            const parameters = {
                'categoryData': JSON.stringify(category)
            };
            const data = await this.executeAPIRequest(this.apiConfig.addCategoryURL, parameters);
            category.id = data.category.id;
        });
    }

    updateCategory(category: Category) {
        this.addToQueue(async () => {
            const parameters = {
                'categoryData': JSON.stringify(category)
            };
            await this.executeAPIRequest(this.apiConfig.updateCategoryURL, parameters);
        });
    }

    moveCategory(category: Category, newIndex: number) {
        this.addToQueue(async () => {
            const parameters = {
                'categoryData': JSON.stringify(category),
                'newSort': newIndex + 1
            };
            await this.executeAPIRequest(this.apiConfig.moveCategoryURL, parameters);
        });
    }

    addGradeColumn(gradeColumn: GradeColumn, callback: Function) {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnData': JSON.stringify(gradeColumn)
            };
            const data = await this.executeAPIRequest(this.apiConfig.addColumnURL, parameters);
            callback(data.column, data.scores);
        });
    }

    addColumnSubItem(gradeColumnId: ColumnId, gradeItemId: ItemId, callback: Function) {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnId': gradeColumnId,
                'gradeItemId': gradeItemId
            };
            const data = await this.executeAPIRequest(this.apiConfig.addColumnSubItemURL, parameters);
            callback(data.column, data.scores);
        });
    }

    removeColumnSubItem(gradeColumnId: ColumnId, gradeItemId: ItemId, callback: Function) {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnId': gradeColumnId,
                'gradeItemId': gradeItemId
            };
            const data = await this.executeAPIRequest(this.apiConfig.removeColumnSubItemURL, parameters);
            callback(data.column, data.scores);
        });
    }

    updateGradeColumn(gradeColumn: GradeColumn) {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnData': JSON.stringify(gradeColumn)
            };
            await this.executeAPIRequest(this.apiConfig.updateColumnURL, parameters);
        });
    }

    updateGradeColumnCategory(gradeColumn: GradeColumn, categoryId: number|null)
    {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnId': gradeColumn.id,
                'categoryId': categoryId
            };
            await this.executeAPIRequest(this.apiConfig.updateColumnCategoryURL, parameters);
        });
    }

    moveGradeColumn(gradeColumn: GradeColumn, newIndex: number) {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnId': gradeColumn.id,
                'newSort': newIndex + 1
            };
            await this.executeAPIRequest(this.apiConfig.moveColumnURL, parameters);
        });
    }

    removeGradeColumn(gradeColumn: GradeColumn) {
        this.addToQueue(async () => {
            const parameters = {
                'gradeColumnId': gradeColumn.id
            };
            await this.executeAPIRequest(this.apiConfig.removeColumnURL, parameters);
        });
    }

    protected addToQueue(callback: Function) {
        this.queue.add(async () => {
            await callback();
        });
        this.queue.onIdle().then(this.finishSaving);
    }

    private async executeAPIRequest(apiURL: string, parameters: any = {}) {
        this.beginSaving();

        parameters['gradebookDataId'] = this.gradebookDataId;
        parameters['version'] = this.currentVersion;

        const formData = new FormData();
        if (this.apiConfig.csrfToken) {
            formData.set('_csrf_token', this.apiConfig.csrfToken);
        }
        for (const [key, value] of Object.entries(parameters)) {
            formData.set(key, value as any);
        }

        try {
            const res = await axios.post(apiURL, formData, {timeout: TIMEOUT_SEC * 1000});
            this.logResponse(res.data);
            if (typeof res.data === 'object') {
                this.gradebookDataId = res.data.gradebook.dataId;
                this.currentVersion = res.data.gradebook.version;
                return res.data;
            } else if (typeof (res.data as unknown) === 'string' && res.data.indexOf('formLogin') !== -1) {
                throw { 'type': 'LoggedOut' };
            } else {
                throw { 'type': 'Unknown' };
            }
        } catch (err) {
            console.log(err);
            let error: any;
            if (err?.isAxiosError && err.message?.toLowerCase().indexOf('timeout') !== -1) {
                error = { 'type': 'Timeout' };
            } else if (err?.response?.data?.error) {
                error = err.response.data.error;
            } else if (err?.type) {
                error = err;
            } else {
                error = { 'type': 'Unknown' };
            }
            this.errorListeners.forEach(errorListener => errorListener.setError(error));
        }
    }

    logResponse(data: any) {
        const responseEl = document.getElementById('server-response');
        if (responseEl) {
            if (typeof data === 'object') {
                responseEl.innerHTML = JSON.stringify(data, null, 4);
            } else {
                responseEl.innerHTML = `<div>An error occurred:</div>${data}`;
            }
        }
    }
}