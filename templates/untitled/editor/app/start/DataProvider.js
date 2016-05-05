/* exported DataProvider */
/* global SessionTimeoutError, DataProviderHelper, ServerPermissionError, MultiInstanceError, $ */

var DataProvider = (function () {
    'use strict';

    var context   = window,
        config    = context.config,
        provider  = {};

    provider.validateResponse = function validateResponse(xhr) {
        var error = DataProviderHelper.validateRequest(xhr);
        if (!error) {
            var response = xhr.responseText;
            if (typeof response === 'string' && '' !== response) {
                var parts = response.split('[app.lock]');
                if (parts.length >= 3 && parts[parts.length - 1] === '') {
                    error = new MultiInstanceError('LOCK:' + parts[parts.length - 2]);
                }
                try {
                    var result = JSON.parse(response);
                    if (result.error === 'sessions') {
                        error = new SessionTimeoutError();
                        error.loginUrl = window.location.href;
                    } else if (result.error === 'permissions') {
                        error = new ServerPermissionError(result.message);
                    } else if (result.status === 'error' && result.message) {
                        error = new Error(result.message);
                    }
                } catch (e) {
                }
            }
        }
        return error;
    };

    function ajaxFailHandler(url, xhr, status, callback) {
        var error = DataProvider.validateResponse(xhr);
        if (!error) {
            error = DataProviderHelper.createCmsRequestError(url, xhr, status);
        }
        callback(error);
    }

    function buildUrl(action) {
        var str = config.index + '?action=' + action + '&template=' + config.templateName;
        if (config.instanceId)
            str += '&instanceId=' + config.instanceId;
        return str;
    }
    provider.reloadTemplatesInfo = function reloadTemplatesInfo(callback) {
        var url = buildUrl('getTemplates');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                frontend: true
            },
            success: function reloadTemplatesInfoSuccess(data, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    $.each(data, function(key, value) {
                        config.infoData[key] = value;
                    });
                }
                callback(error);
            },
            error: function reloadTemplatesInfoFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.reloadThemesInfo = function reloadThemesInfo(callback) {
        var url = buildUrl('getThemes');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {},
            success: function reloadThemesInfoSuccess(data, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    config.infoData.themes = data;
                }
                callback(error, JSON.stringify(config.infoData));
            },
            error: function reloadThemesInfoFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.backToAdmin = function backToAdmin() {
        var currentUrl = context.location.href,
            index = currentUrl.lastIndexOf('&editor=1'),
            url = currentUrl.substr(0, index);
        context.location.replace(url);
    };

    provider.getMaxRequestSize = function getMaxRequestSize() {
        return config.infoData.maxRequestSize;
    };

    provider.doExport = function doExport(data, callback) {
        var request = {
            'save': {
                'post': {
                    data: JSON.stringify(data)
                },
                'url': buildUrl('doExport')
            },
            'clear': {
                'post': {},
                'url': buildUrl('clearChunks')
            },
            'errorHandler': DataProvider.validateResponse,
            'zip': true,
            'blob': true
        };
        DataProviderHelper.chunkedRequest(request, callback);
    };

    provider.save = function save(saveData, callback) {
        var request = {
            'save': {
                'post': {
                    data: JSON.stringify(saveData)
                },
                'url': buildUrl('saveProject')
            },
            'clear': {
                'post': {},
                'url': buildUrl('clearChunks')
            },
            'errorHandler': DataProvider.validateResponse,
            'zip': true,
            'blob': true
        };
        DataProviderHelper.chunkedRequest(request, callback);
    };

    provider.updatePlugins = function updatePlugins(callback) {
        var url = buildUrl('updatePlugins');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {},
            success: function updatePluginsSuccess(response, status, xhr) {
                callback(DataProvider.validateResponse(xhr));
            },
            error: function updatePluginsFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    }

    provider.updatePreviewTheme = function updatePreviewTheme(callback) {
        callback();
    };

    provider.getTheme = function getTheme(options, callback) {
        var themeName = options.themeName || config.templateName,
            url = buildUrl('getThemeZip') + '&themeName=' + themeName + '&includeEditor=' + options.includeEditor;
        callback(null, url);
    };

    provider.themeArchiveExt = 'zip';

    provider.canRename = function canRename(themeName, callback) {
        var url = buildUrl('canRename');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                themeName: themeName
            },
            success: function canRenameSuccess(can, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    callback(null, can);
                } else {
                    callback(error);
                }
            },
            error: function canRenameFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.rename = function rename(themeName, callback) {
        var url = buildUrl('renameTheme');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                oldThemeName: config.templateName,
                newThemeName: themeName || config.templateName
            },
            success: function renameSuccess(response, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    var href = context.location.href,
                        name = config.templateName,
                        regExp = new RegExp('theme=' + name);
                    if (href.search(regExp) === -1) {
                        href = href.replace('editor=1', 'editor=1&theme=' +  themeName);
                    } else {
                        href = href.replace(regExp, 'theme=' + themeName);
                    }
                    callback(null, href);
                } else {
                    callback(error);
                }
            },
            error: function renameFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.getFiles = function getFiles(mask, filter, callback) {
        var url = buildUrl('getFiles');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                mask: mask || '*',
                filter: filter || ''
            },
            success: function getFilesSuccess(response, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    callback(null, response.files);
                } else {
                    callback(error);
                }
            },
            error: function getFilesFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.setFiles = function setFiles(files, callback) {
        var request = {
            'save': {
                'post': {
                    data: JSON.stringify(files)
                },
                'url': buildUrl('setFiles')
            },
            'clear': {
                'post': {},
                'url': buildUrl('clearChunks')
            },
            'errorHandler': DataProvider.validateResponse,
            'zip': true,
            'blob': true
        };
        DataProviderHelper.chunkedRequest(request, callback);
    };

    provider.zip = function zip(data, callback) {
        var request = {
            'save': {
                'post': {
                    data: JSON.stringify(data)
                },
                'url': buildUrl('zip')
            },
            'clear': {
                'post': {},
                'url': buildUrl('clearChunks')
            },
            'errorHandler': DataProvider.validateResponse,
            'zip': true,
            'blob': true
        };
        DataProviderHelper.chunkedRequest(request, callback);
    };

    provider.getEditableContent = function getEditableContent(contentId, callback) {
        var url = buildUrl('getEditableContent');

        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                contentId: contentId
            },
            success: function getEditableContentSuccess(data, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (error) {
                    callback(error);
                } else {
                    callback(null, data);
                }
            },
            error: function getEditableContentFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.putEditableContent = function putEditableContent(data, callback) {
        var url = buildUrl('putEditableContent');

        var request = {
            'save': {
                'post': {
                    data: JSON.stringify(data)
                },
                'url': url
            },
            'clear': {
                'post': { action: 'clearChunks' },
                'url': url
            },
            'errorHandler': DataProvider.validateResponse,
            'zip': true,
            'blob': true
        };
        DataProviderHelper.chunkedRequest(request, callback);
    };

    function getIdsFromHtml(domOuterHTML) {
        return (domOuterHTML.match(/data-post-id-\d+/g) || []).map(function (s) {
            return s.replace('data-post-id-', '');
        });
    }

    provider.getCmsContent = function getCmsContent(getData, callback) {
        $.each(getData, function(type, data) {
            if (data.domOuterHTML) {
                data.ids = getIdsFromHtml(data.domOuterHTML);
                delete data.domOuterHTML;
            }
        });
        var url = buildUrl('getCmsContent');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                data: getData,
                template : config.templateName
            },
            success: function getCmsContentSuccess(data, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (error) {
                    callback(error);
                } else {
                    callback(null, data);
                }
            },
            error: function getCmsContentFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.putCmsContent = function putCmsContent(putData, callback) {
        $.each(putData, function(type, data) {
            if (data.domOuterHTML) {
                data.idsToRemove = getIdsFromHtml(data.domOuterHTML);
                delete data.domOuterHTML;
            }
        });
        var url = buildUrl('putCmsContent');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                data: putData,
                template : config.templateName,
                styleId : config.styleId
            },
            success: function putCmsContentSuccess(data, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (error) {
                    callback(error);
                } else {
                    callback(null, data);
                }
            },
            error: function putCmsContentFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.load = function () {
        return JSON.parse(context.atob(config.projectData)) || {};
    };

    provider.getAllCssJsSources = function () {
        return config.cssJsSources;
    };

    provider.getMd5Hashes = function () {
        return config.md5Hashes;
    };

    provider.getThemeVersion   = function () {
        return config.revision;
    };

    provider.makeThemeAsActive = function makeThemeAsActive(callback, id) {
        var url = buildUrl('makeThemeAsActive');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                themeId: id || ''
            },
            success: function themeActiveSuccess(response, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                callback(error);
            },
            error: function themeActiveFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.renameTheme = function renameTheme(themeName, newName, callback) {
        var url = buildUrl('renameTheme');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                oldThemeName: themeName,
                newThemeName: newName
            },
            success: function renameSuccess(response, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    var href = context.location.href,
                        name = config.templateName,
                        regExp = new RegExp('theme=' + name);
                    if (name.search(regExp) === -1) {
                        href = href.replace('editor=1', 'editor=1&theme=' +  themeName);
                    } else {
                        href = href.replace(regExp, 'theme=' + newName);
                    }
                    callback(null, config.templateName === themeName ? href : null);
                } else {
                    callback(error);
                }
            },
            error: function renameFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.removeTheme = function removeTheme(id, callback) {
        var url = buildUrl('removeTheme');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                templateId: id
            },
            success: function removeSuccess(response, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    callback(null);
                } else {
                    callback(error);
                }
            },
            error: function removeFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.copyTheme = function copyTheme(id, newName, callback) {
        var url = buildUrl('copyTheme');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                templateId: id,
                newThemeName: newName || ''
            },
            success: function copySuccess(response, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    callback(null);
                } else {
                    callback(error);
                }
            },
            error: function copyFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.getPosts = function getPosts(searchObj, callback) {
        var url = buildUrl('getPosts');
        $.ajax({
            type: "post",
            url: url,
            dataType: "json",
            data: {
                searchObject : searchObj
            },
            success: function getPostsSuccess(data, status, xhr) {
                var error = DataProvider.validateResponse(xhr);
                if (!error) {
                    callback(null, data);
                } else {
                    callback(error);
                }
            },
            error: function getPostsFail(xhr, status) {
                ajaxFailHandler(url, xhr, status, callback);
            }
        });
    };

    provider.getInfo = function() {
        var info = {
            cmsName : 'Joomla',
            cmsVersion : config.infoData.cmsVersion,
            adminPage: config.infoData.adminPage,
            contentManagerPage: config.infoData.contentManagerPage,
            startPage: config.infoData.startPage,
            templates: config.infoData.templates,
            canDuplicateTemplatesConstructors : config.infoData.canDuplicateTemplatesConstructors,
            thumbnails : [
                { name: 'template_preview.png', width: 800, height: 600 },
                { name: 'template_thumbnail.png', width: 206, height: 150 }
            ],
            themeName : config.templateName,
            isThemeActive : config.infoData.isThemeActive,
            uploadImage : buildUrl('uploadImage'),
            uploadTheme : buildUrl('uploadTheme'),
            unZip: buildUrl('unZip'),
            themes : $.extend({}, config.infoData.themes),
            pathToManifest : '/app/themler.manifest',
            contentEditorPluginVersion : true
        };
        if (typeof(config.infoData.contentIsImported) !== 'undefined' && false === config.infoData.contentIsImported) {
            info.importContent = buildUrl('importContent') + '&id=' + config.styleId;
            info.replaceContent = buildUrl('importContent') + '&id=' + config.styleId;
        }
        return info;
    };

    provider.getVersion = function () {
        return "0.0.2";
    };

    provider.escapeCustomCode = function (content) {
        return "<?php\necho <<<'CUSTOM_CODE'\n" + content + "\nCUSTOM_CODE;\n?>";
    };

    return provider;
}());