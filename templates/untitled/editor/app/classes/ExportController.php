<?php

require_once EXPORT_APP . '/Storage.php';
require_once EXPORT_APP . '/ThemeBuilder.php';
require_once EXPORT_APP . '/Chunk.php';
require_once EXPORT_APP . '/Config.php';
require_once EXPORT_APP . '/Archive.php';

class ExportController
{
    public function __construct() {}

    /**
     * @param $data
     */
    public function execute($data)
    {
        try {
            $app = JFactory::getApplication();
            $styleId = Config::getStyleObject()->id;
            if (isset($data['auto_authorization']) && 1 === (int)$data['auto_authorization']) {
                $params = array();
                $username = '';
                if (isset($data['username'])) {
                    $params[] = 'login=' . $data['username'];
                    $username = $data['username'];
                }
                $password = '';
                if (isset($data['password'])) {
                    $params[] = 'password=' . $data['password'];
                    $password = $data['password'];
                }
                if (isset($data['domain']))   $params[] = 'domain=' . urlencode($data['domain']);
                if (isset($data['ver']))      $params[] = 'ver=' . $data['ver'];
                if (isset($data['startup']))  $params[] = 'startup=' . $data['startup'];
                if (isset($data['desktop']))  $params[] = 'desktop=' . $data['desktop'];
                if (count($params) > 0)       $params = '&' . implode('&', $params);

                if ('' !== $username) {
                    $credentials = array( 'username' => $username, 'password' => $password);
                    $app->login($credentials, array('action' => 'core.login.admin'));
                }

                if (isset($data['returnUrl']) && '' !== $data['returnUrl'])
                    $app->redirect($data['returnUrl']);

                $current = dirname(JURI::current()) . '/';
                $return = dirname(dirname(dirname($current))) . '/administrator/';

                $session = JFactory::getSession();
                $registry = $session->get('registry');
                if (null !== $registry)
                    $registry->set('com_templates.edit.style.id', $styleId);

                if ($styleId) {
                    $return .= 'index.php?option=com_templates&view=style&layout=edit&id=' .
                        $styleId . '&editor=1&theme=' . Config::getStyleObject()->template .  $params;
                }
                $app->redirect($return);
            }

            Helper::tryAllocateMemory();

            // checking user privileges
            $user = JFactory::getUser();
            $session = JFactory::getSession();
            if (!isset($data['frontend']) && false == getenv('THEMLER_MANIFEST_STORAGE') &&
                !(1 !== (integer)$user->guest && 'active' === $session->getState())) {
                $registry = $session->get('registry');
                if (null !== $registry)
                    $registry->set('com_templates.edit.style.id', $styleId);
                echo $this->_response(array('error' => 'sessions'));
            } else {
                $parts = pathinfo(dirname(dirname(dirname(__FILE__))));
                $lockFile = JPATH_SITE . '/templates/' . $parts['filename'] . '/app/app.lock';
                if (!isset($data['outsideEditor']) && file_exists($lockFile)) {
                    $instanceId = Helper::readFile($lockFile);
                    if (!isset($data['instanceId']) || (isset($data['instanceId']) && $instanceId !== $data['instanceId']))
                        die('[app.lock]' . $instanceId . '/' . (isset($data['instanceId']) ? $data['instanceId'] : '0') . '[app.lock]');
                }
                echo $this->{$data['action']}($data);
            }
        } catch (PermissionsException $e) {
            echo $this->_response(array('error' => 'permissions', 'message' => $e->getMessage()));
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    public function doExport($data)
    {
        return $this->_export($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function saveProject($data)
    {
        return $this->_export($data);
    }

    /**
     * @param $data
     * @return mixed
     */
    private function _export($data)
    {
        $timeLogging = LoggingTime::getInstance();

        $timeLogging->start('[PHP] Get chunk info');

        $info = isset($data['info']) ?
            json_decode($data['info'], true) :
            $this->_getChunkInfo($data);

        $timeLogging->end('[PHP] Get chunk info');

        if (null === $info) {
            trigger_error('Chunk is failed - "' . $data['info'] . '"', E_USER_ERROR);
        }

        $timeLogging->start('[PHP] Chunk save');

        $chunk = new Chunk();
        $chunk->save($info);

        $timeLogging->end('[PHP] Chunk save');

        if ($chunk->last()) {
            $timeLogging->start('[PHP] Decode json result');
            $result = $chunk->complete();
            if ($result['status'] === 'done') {
                $result = json_decode($result['data'], true);
            } else {
                $timeLogging->end('[PHP] Decode json result');
                $timeLogging->end('[PHP] Build Theme');
                $timeLogging->end('[PHP] Joomla start of work');
                $result['result'] = 'error';
                $result['log'] = $timeLogging->getLog();
                return $this->_response(array($result));
            }
            $timeLogging->end('[PHP] Decode json result');
            // icon fonts collection
            $icons = array_key_exists('iconSetFiles', $result) > 0 ? $result['iconSetFiles'] : '';
            // thumbnails collection
            $thumbnails = array_key_exists('thumbnails', $result) > 0 ? $result['thumbnails'] : '';
            // custom images collection
            $images = array_key_exists('images', $result) > 0 ? $result['images'] : '';
            // storage for media files
            $media = array('icons' => $icons, 'thumbnails' => $thumbnails, 'images' => $images);
            $templateName = $data['template'];
            $themeDir = JPATH_SITE . '/templates/' . $templateName;

            $timeLogging->start('[PHP] Build Theme');
            $timeLogging->start('[PHP] Initializing theme builder');
            $themeBuilder = new ThemeBuilder($templateName, $media);
            $timeLogging->end('[PHP] Initializing theme builder');
            $storage = array();
            if (array_key_exists('themeFso', $result)) {
                $timeLogging->start('[PHP] Build file storage from themeFso');
                $storage = Helper::buildThemeStorage($result['themeFso']);
                $timeLogging->end('[PHP] Build file storage from themeFso');
            }
            $timeLogging->start('[PHP] Export doing');
            $themeBuilder->export($storage);
            $timeLogging->end('[PHP] Export doing');

            if ('saveProject' === $data['action']) {

                $timeLogging->start('[PHP] Update original theme');
                $themeBuilder->updateOriginalTheme();
                $timeLogging->end('[PHP] Update original theme');

                if (array_key_exists('projectData', $result)) {
                    $timeLogging->start('[PHP] Save project json');
                    $projectFile = $themeDir . '/app/project.json';
                    $project = new Project($projectFile);
                    $project->refresh(array('projectdata' => $result['projectData']));
                    $project->save();
                    $timeLogging->end('[PHP] Save project json');
                }

                if (array_key_exists('cssJsSources', $result)) {
                    $timeLogging->start('[PHP] Save css/js sources');
                    $cacheFile = $themeDir . '/app/cache.json';
                    $cache = new Cache($cacheFile);
                    $cache->refresh($result['cssJsSources']);
                    $cache->save();
                    $timeLogging->end('[PHP] Save css/js sources');
                }

                if (array_key_exists('md5Hashes', $result)) {
                    $timeLogging->start('[PHP] Save md5 file hashes');
                    $hashesFile = $themeDir . '/app/hashes.json';
                    $haches = new Hash($hashesFile);
                    $haches->refresh($result['md5Hashes']);
                    $haches->save();
                    $timeLogging->end('[PHP] Save md5 file hashes');
                }

                if (file_exists($themeDir . '/data/converter.data'))
                    Helper::deleteFile($themeDir . '/data/converter.data');

                $this->updatePlugins($data);
            }
            $timeLogging->end('[PHP] Build Theme');
            $timeLogging->end('[PHP] Joomla start of work');
            return $this->_response(array('result' => 'done', 'log' => $timeLogging->getLog()));
        } else {
            $timeLogging->end('[PHP] Joomla start of work');
            return $this->_response(array('result' => 'processed', 'log' => $timeLogging->getLog()));
        }    
    }

    /**
     * @param $data
     * @return mixed
     */
    public function updatePlugins($data) {
        $pluginsFolder = JPATH_SITE . '/templates/' . $data['template'] . '/editor/plugins';

        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_installer');
        define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_installer');
        define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_installer');

        if (file_exists($pluginsFolder)) {
            $this->_updatePlugin('button', $pluginsFolder);
            $this->_updatePlugin('content', $pluginsFolder);
        }
        return $this->_response('updated');
    }

    /**
     * @param $name
     * @param $pluginsFolder
     */
    private  function _updatePlugin($name, $pluginsFolder)
    {
        $pluginFolder = $pluginsFolder . '/' . $name;
        if (!file_exists($pluginFolder))
            return;
        $doInstall = false;
        $doEnable = false;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__extensions'))
            ->where('type = ' . $db->quote('plugin'))
            ->where('element = ' . $db->quote('themler' . $name));
        $db->setQuery($query);
        $result = $db->loadObject();

        if ($result) {
            $manifestObject = json_decode($result->manifest_cache);
            $xml = simplexml_load_string(Helper::readFile($pluginFolder . '/themler' . $name . '.xml'));
            if ($manifestObject && $xml) {
                if (version_compare($manifestObject->version,  $xml->version) == -1) {
                    $doInstall = true;
                }
            } else {
                $doInstall = true;
            }
            if (!$doInstall && 'content' == $name) {
                $pluginContentShortcodes = Helper::readFile(JPATH_PLUGINS . '/content/themlercontent/lib/Shortcodes.php');
                $previewContentShortcodes = Helper::readFile($pluginFolder . '/lib/Shortcodes.php');
                if ($pluginContentShortcodes !== $previewContentShortcodes) {
                    $doInstall = true;
                }
            }
        } else {
            $doInstall = true;
            $doEnable = true;
        }

        if ($doInstall) {
            if ($name == 'content') {
                Helper::copyFile(dirname($pluginsFolder) . '/css/BillionWebFonts.ttf', $pluginFolder . '/lib/BillionWebFonts.ttf');
                Helper::copyFile(dirname($pluginsFolder) . '/css/BillionWebFonts.woff', $pluginFolder . '/lib/BillionWebFonts.woff');
                Helper::copyFile(dirname($pluginsFolder) . '/css/bootstrap.css', $pluginFolder . '/lib/bootstrap.css');
                Helper::copyFile(dirname($pluginsFolder) . '/bootstrap.min.js', $pluginFolder . '/lib/bootstrap.min.js');
                Helper::copyFile(dirname($pluginsFolder) . '/jquery.js', $pluginFolder . '/lib/jquery.js');
            }
            $app = JFactory::getApplication('administrator');
            // Create token
            $session = JFactory::getSession();
            $token = $session::getFormToken();

            // Load translations
            $lang = JLanguage::getInstance('en-GB');
            $lang->load('lib_joomla', JPATH_ADMINISTRATOR);
            $lang->load('com_installer', JPATH_BASE, null, false, true) ||
            $lang->load('com_installer', JPATH_COMPONENT, null, false, true);

            if (version_compare(JVERSION, '3.0', '<')) {
                $_SERVER['REQUEST_METHOD'] = 'post';
                JRequest::setVar('installtype', 'folder', 'post');
                $defaultTask =  JRequest::getVar('task', 'install.install');
                JRequest::setVar('task', $defaultTask, 'post');
                JRequest::setVar('install_directory', $pluginFolder, 'post');
                // Register the language object with JFactory
                JFactory::$language = $lang;
                JRequest::setVar($token, 1, 'post');
            } else {
                $app->input->set('installtype', 'folder');
                $app->input->set('task', 'install.install');
                $app->input->set('install_directory', $pluginFolder);
                // Register the language object with JFactory
                $app->loadLanguage($lang);
                JFactory::$language = $app->getLanguage();
                $app->input->post->set($token, 1);
            }
            // Execute installing
            $controller	= JControllerLegacy::getInstance('Installer');
            $controller->execute(JRequest::getCmd('task'));

            $messages = $app->getMessageQueue();
            $successMessage = JText::sprintf('COM_INSTALLER_INSTALL_SUCCESS', JText::_('COM_INSTALLER_TYPE_TYPE_PLUGIN'));
            $result = '';
            $permissionErrorFound = false;
            foreach($messages as $message) {
                if ($message['message'] == $successMessage) {
                    $result = '';
                    break;
                }
                if (strpos($message['message'], 'create directory') !== -1) {
                    $permissionErrorFound = true;
                }
                $result .= $message['message'] . "\n";
            }

            if ('' !== $result && $permissionErrorFound)
                throw new PermissionsException($result);

            if ('' !== $result)
                trigger_error('Plugin is not installed. Plugin name:' . $name . ' Error: ' . $result, E_USER_ERROR);

            if ($doEnable) {
                $query = $db->getQuery(true);
                $query->update('#__extensions');
                $query->set('enabled=1');
                $query->where('type = ' . $db->quote('plugin'));
                $query->where('element = ' . $db->quote('themler' . $name));
                $db->setQuery($query);
                $db->query();
            }
        }

        $originalPluginsDir = dirname(dirname($pluginsFolder)) . '/plugins';
        if ($doInstall || !file_exists($originalPluginsDir . '/' . $name . '.zip')) {
            $zipFilesArray = array();
            $flags = FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginFolder, $flags));
            foreach ($iterator as $fileInfo) {
                $pname = $iterator->getSubPathname();
                $data = Helper::readFile($fileInfo->getPathName());
                $zipFilesArray[] = array('name' => $name . '/' . $pname, 'data' => $data);
            }
            jimport('joomla.filesystem.archive');
            jimport('joomla.filesystem.file');
            $zip = JArchive::getAdapter('zip');
            Helper::createDir($originalPluginsDir);
            Helper::removeDir($originalPluginsDir . '/' . $name);
            $zip->create($originalPluginsDir . '/' . $name . '.zip', $zipFilesArray);
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    public function clearChunks($data)
    {
        $chunk = new Chunk();
        $uploadPath = $chunk->UPLOAD_PATH;
        if (($id = $data['id']) && $id && is_dir($uploadPath . $id)) {
            Helper::removeDir($uploadPath . $id);
            return $this->_response('ok');
        } else {
            return $this->_response('fail');
        }    
    }

    /**
     * @param $data
     * @return mixed
     */
    public function updatePreview($data)
    {
        return $this->_response('updated');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function setParameters($data)
    {
        $id = isset($data['styleId']) && is_string($data['styleId'])
                && ctype_digit($data['styleId']) ? intval($data['styleId'], 10) : -1;
        if (-1 !== $id)
            $this->_setPatameters($id, $data['params']);
        return $this->_response('parameters setted');
    }

    public function runUp($data)
    {
        // testing 16M of memory
        $func = create_function("", "echo json_encode(array(error => 'memtest'));");
        $callback = new UnregisterableCallback($func);
        register_shutdown_function(array($callback, "call"));
        @str_repeat('.', 16 * 1024 * 1024);
        $callback->unregister();

        // check memory allocating
        if(!Helper::tryAllocateMemory()) {
            return $this->_response(array(
                'error' => 'memdata',
                'amount' => Helper::getMemoryLimit()
            ));
    }

        return $this->_response(array(
            'result' => 'done',
            'version' => Config::buildAppManifestVersion($data['template'])
        ));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function renameTheme($data)
    {
        $oldThemeName = $data['oldThemeName'];
        $lowerOldThemeName = strtolower($oldThemeName);
        $newThemeName = $data['newThemeName'];
        $lowerNewThemeName = strtolower($newThemeName);

        $oldThemeDir = JPATH_SITE . '/templates/' . $lowerOldThemeName;
        $newThemeDir = JPATH_SITE . '/templates/' . $lowerNewThemeName;

        Helper::moveDir($oldThemeDir, $newThemeDir);
        // manifest correction
        $manifest = $newThemeDir . '/templateDetails.xml';
        $content = Helper::readFile($manifest);
        $xml = simplexml_load_string($content);
        $xml->name = $newThemeName;
        $path = $xml->config->fields['addfieldpath'];
        $xml->config->fields['addfieldpath'] = str_replace($lowerOldThemeName, $lowerNewThemeName, $path);
        foreach($xml->languages->language as $node) {
            $language = $node[0];
            $node[0] = str_replace($lowerOldThemeName, $lowerNewThemeName, $language);
        }
        // Save dom to xml file
        if (class_exists('DOMDocument')) {
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $data = $dom->saveXML();
        } else {
            $data = $xml->asXML();
        }
        Helper::writeFile($manifest, $data);
        // translation file correction
        $translateDir = $newThemeDir . '/language/en-GB/';
        Helper::renameFile($translateDir . 'en-GB.tpl_' . $lowerOldThemeName . '.ini',
            $translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.ini');
        if (file_exists($translateDir . 'en-GB.tpl_' . $lowerOldThemeName . '.sys.ini')) {
            Helper::renameFile($translateDir . 'en-GB.tpl_' . $lowerOldThemeName . '.sys.ini',
                $translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.sys.ini');
            $content = Helper::readFile($translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.sys.ini');
            $content = str_replace(strtoupper($lowerOldThemeName), strtoupper($lowerNewThemeName), $content);
            Helper::writeFile($translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.sys.ini', $content);
        }

        //Changes the theme in database
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('title');
        $query->from('#__template_styles');
        $query->where('template=' . $db->quote($lowerOldThemeName));
        $db->setQuery($query);
        $title = $db->loadResult();

        $query = $db->getQuery(true);
        $query->select('manifest_cache, name');
        $query->from('#__extensions');
        $query->where('type=' . $db->quote('template')  . ' and element=' . $db->quote($lowerOldThemeName));
        $db->setQuery($query);
        $ret = $db->loadAssoc();
        $manifest_cache = $ret['manifest_cache'];
        $originalOldThemeName = $ret['name'];

        $query = $db->getQuery(true);
        $query->update('#__template_styles');
        $query->set('template=' . $db->quote($lowerNewThemeName));
        $query->set('title=' . $db->quote(str_replace($originalOldThemeName, $newThemeName, $title)));
        $query->where('template=' . $db->quote($lowerOldThemeName));
        $db->setQuery($query);
        $db->query();

        $query = $db->getQuery(true);
        $query->update('#__extensions');
        $query->set('name=' . $db->quote($newThemeName));
        $query->set('element=' . $db->quote($lowerNewThemeName));
        $query->set('manifest_cache=' . $db->quote(str_replace($originalOldThemeName, $newThemeName, $manifest_cache)));
        $query->where('type=' . $db->quote('template')  . ' and element=' . $db->quote($lowerOldThemeName));
        $db->setQuery($query);
        $db->query();
        if ($oldThemeDir !== $newThemeDir)
            Helper::removeDir($oldThemeDir);
        return $this->_response('renamed');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getTemplates($data)
    {
        $templates = Config::getThemeTemplates(true);
        return $this->_response(array(
            'templates' => $templates['templates'],
            'contentIsImported' => Config::contentIsImported(),
            'startPage' => $templates['home']
            ));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getThemes($data = array())
    {
        $current = dirname(JURI::current()) . '/';
        $root = dirname(dirname(dirname($current)));
        $result = array();
        $items = $this->_getThemesList();
        foreach($items as $item) {
            $themeDir = JPATH_SITE . '/templates/' . $item->element;
            if (!file_exists($themeDir . '/app/project.json'))
                continue;
            $thumbnailDir = file_exists($themeDir . '/template_thumbnail.png') ?
                $root . '/templates/' . $item->element . '/template_thumbnail.png' : '';
            $versionPath = $themeDir . '/app/themler.version';
            $version = file_exists($versionPath) ? '&ver=' . Helper::readFile($versionPath) : '';
            $openUrl = $root . '/templates/' . $item->element .
                '/app/index.php?auto_authorization=1&username=&password=&domain=' . $version;
            $manifestObject = json_decode($item->manifest_cache);
            $result[$item->id] = array(
                'themeName' => $item->element,
                'thumbnailUrl' => $thumbnailDir,
                'themeTitle' => ($manifestObject ? $manifestObject->name : ''),
                'openUrl' => $openUrl,
                'isActive' => $this->themeIsActive($item->element)
            );
        }
        return $this->_response($result);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function getPosts($data = array())
    {
        $timeLogging = LoggingTime::getInstance();

        $timeLogging->start('[PHP] Get Posts');

        $searchObject = $data['searchObject'];
        $themeName = $data['template'];

        $postType = isset($searchObject['postType']) ? $searchObject['postType'] : 'article';
        $searchString = isset($searchObject['searchString']) ? $searchObject['searchString'] : '';
        $pageNumber = isset($searchObject['pageNumber']) ? $searchObject['pageNumber'] : 0;
        $pageSize = isset($searchObject['pageSize']) ? $searchObject['pageSize'] : 10;
        $sortType = isset($searchObject['sortType']) ? $searchObject['sortType'] : 'default';

        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__content');
        $query->where('state = 1');
        if ($searchString) {
            $query->where('title like "%' . $searchString . '%"');
        }
        switch($sortType) {
            case 'newest':
                $query->order('created', 'desc');
                break;
            case 'popular':
                $query->order('hits', 'desc');
                break;
            case 'random':
                $query->order('rand()');
                break;
            default:
                $query->order('title');
        }
        if ($pageNumber == 1) $pageNumber = 0;
        $db->setQuery($query, (int)$pageNumber, (int)$pageSize);
        $list = $db->loadObjectList();

        $db = JFactory::getDbo();
        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__menu'))
            ->where('home = 1')
            ->where('published = 1')
            ->where('language = ' . $db->quote('*'))
            ->where('client_id = 0');
        $db->setQuery($query);
        $Itemid = $db->loadResult();

        require_once JPATH_SITE . '/components/com_content/helpers/route.php';
        $root = dirname(dirname(dirname(dirname(JURI::current()))));
        $result = array();
        if (null !== $Itemid) {
            foreach($list as $item) {
                $url = $root . '/' . ContentHelperRoute::getArticleRoute($item->id);
                $url = preg_replace('/&Itemid=(\d+)/', '&Itemid=' . $Itemid, $url);
                $params = new JRegistry();
                $params->loadString($item->attribs);
                $isPage = Config::isPageCheckItem($params);
                if (($postType == 'page' && !$isPage) ||
                    ($postType != 'page' && $isPage))
                    continue;
                $parts = explode('-', JComponentHelper::getParams('com_languages')->get('site', 'en-GB'));
                if ($parts > 1)
                    $url .= '&lang=' . array_shift($parts);
                $url .= '&template=' . $themeName . '&is_preview=on';
                $result[] = array(
                    'name' => $item->id,
                    'caption' => $item->title,
                    'url' => $url,
                    'id' => $item->id
                );
            }
        }
        $timeLogging->end('[PHP] Get Posts');
        $timeLogging->end('[PHP] Joomla start of work');
        return $this->_response(array('logs' => $timeLogging->getLog(), 'data' => $result));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getFiles($data)
    {
        $mask = $data['mask'];
        $filter = $data['filter'];
        $template = $data['template'];

        $templateDir = JPATH_SITE . '/templates/' . $template;

        $flags = 0;
        $matches = array();
        if (defined('GLOB_BRACE')) {
            $flags = GLOB_BRACE;
            $matches[] = '{' . $mask . '}';
        } else {
            $matches = explode(',', $mask);
        }
        $out_files = array();
        foreach($matches as $match) {
            $files = $this->_getFiles($templateDir . '/' . $match, $flags);
            foreach ($files as $file) {
                $name = str_replace($templateDir, '', $file);
                $name = preg_replace('#[\/]+#', '/', $name);
                $info = pathinfo($file);
                $filename = $info['basename'];
                if (is_dir($file) || preg_match("#editor\.css|print\.css|ie\.css#", $filename) || ($filter && preg_match("#$filter#", $filename))
                    || preg_match("#^\/editor\/#", $name)) {
                    continue;
                }

                $out_files[$name] = Helper::readFile($file);
            }
        }
        return $this->_response(array('files' => $out_files));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function setFiles($data)
    {
        $files = array();

        if (isset($data['files'])) {
            $files = json_decode($data['files'], true);
            $response = 'ok';
        } else {
            $chunk = new Chunk();
            $chunk->save($this->_getChunkInfo($data));

            if ($chunk->last()) {
                $result = $chunk->complete();
                if ($result['status'] === 'done') {
                    $files = json_decode($result['data'], true);
                } else {
                    $result['result'] = 'error';
                    return $this->_response(array($result));
                }
                $response = 'done';
            } else {
                return $this->_response('processed');
            }
        }

        if ($files && count($files)) {
            $template = $data['template'];
            $templateDir = JPATH_SITE . '/templates/' . $template;
            foreach ($files as $filename => $content) {
                Helper::writeFile($templateDir . $filename, $content, LOCK_EX);
            }
        }

        return $this->_response($response);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function zip($data)
    {
        $templateName = $data['template'];
        $info = $this->_getChunkInfo($data);

        $chunk = new Chunk();
        $chunk->save($info);

        if (!$chunk->last()) {
            return $this->_response(array('result' => 'processed'));
        }

        $result = $chunk->complete();
        if ($result['status'] === 'done') {
            $data = json_decode($result['data'], true);
        } else {
            $result['result'] = 'error';
            return $this->_response(array($result));
        }

        if (!isset($data['fso']))
            trigger_error('Fso not found' . print_r($data, true), E_USER_ERROR);

        $zipFiles = $this->_convertFsoToZipFiles($data['fso']);
        if (null === $zipFiles) {
            trigger_error('Zip files not found' . print_r($zipFiles, true), E_USER_ERROR);
        }

        $tmp = JPATH_SITE . '/templates/' . $templateName . '/tmp';
        Helper::createDir($tmp);

        jimport('joomla.filesystem.archive');
        jimport('joomla.filesystem.file');
        $archivePath = $tmp . '/' . 'zip-data.zip';
        $zip = JArchive::getAdapter('zip');
        $zip->create($archivePath, $zipFiles);
        $result = array('result' => 'done', 'data' => base64_encode(Helper::readFile($archivePath)));
        Helper::removeDir($tmp);
        return $this->_response($result);
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function unZip($data)
    {
        $templateName = $data['template'];
        $tmp = JPATH_SITE . '/templates/' . $templateName . '/tmp';
        Helper::createDir($tmp);

        $filename = isset($data['filename']) ? $data['filename'] : '';

        if ('' === $filename) {
            $result = array(
                'status' => 'error',
                'message' => 'Empty file name'
            );
        } else {
            $uploadPath = $tmp . '/' . $filename;
            $isLast = isset($data['last']) ? $data['last'] : '';
            $result = $this->_uploadFileChunk($uploadPath, $isLast);

            if ($result['status'] === 'done') {
                $info = pathinfo($uploadPath);
                $suffix = isset($info['extension']) ? '.'.$info['extension'] : '';
                $fileName =  basename($uploadPath, $suffix);
                $extractDir = dirname($uploadPath) . '/' . $fileName;
                Helper::createDir($extractDir);

                if (version_compare(JVERSION, '3.0', '<')) {
                    jimport('joomla.filesystem.archive');
                    $ret = JArchive::extract($uploadPath, $extractDir);
                    if ($ret === false) {
                        return array(
                            'status' => 'error',
                            'message' => 'Invalid type.'
                        );
                    }
                } else {
                    jimport('joomla.filesystem.path');
                    try {
                        JArchive::extract($uploadPath, $extractDir);
                    } catch (Exception $e) {
                        return array(
                            'status' => 'error',
                            'message' => $e->getMessage()
                        );
                    }
                }
                $fso = $this->_convertZipFilesToFso($extractDir);
                Helper::removeDir($tmp);
                $result['fso'] = $fso;
            }
        }
        return $this->_response($result);
    }

    /**
     * @param $path
     * @return array
     */
    public function _convertZipFilesToFso($path) {
        $result = array();
        if (is_file($path)) {
            $type = 'text';
            $content = Helper::readFile($path);
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($ext, array('jpg', 'jpeg', 'bmp', 'png', 'gif', 'svg'))) {
                $type = 'data';
                $content = base64_encode($content);
            }
            return array('type' => $type, 'content' => $content);
        }

        if (is_dir($path)) {
            $result = array('type' => 'dir', 'items' => array());
            if ($dh = opendir($path)) {
                while (($name = readdir($dh)) !== false) {
                    if (in_array($name, array('.', '..'))) {
                        continue;
                    }
                    $result['items'][$name] = $this->_convertZipFilesToFso($path . '/' . $name);
                }
                closedir($dh);
            }
        }

        return $result;
    }

    /**
     * @param $fso
     * @param string $relative
     * @return array|null
     */
    private function _convertFsoToZipFiles($fso, $relative = '')
    {
        static $zipFiles = array();

        if(!array_key_exists('items', $fso) || !is_array($fso['items'])) {
            return null;
        }
        foreach ($fso['items'] as $name => $file) {
            if(isset($file['content']) && isset($file['type'])) {
                switch ($file['type']) {
                    case 'text':
                        $zipFiles[] = array('name' => $relative . $name, 'data' => $file['content']);
                        break;
                    case 'data':
                        $zipFiles[] = array('name' => $relative . $name, 'data' => base64_decode($file['content']));
                        break;
                }
            } elseif(isset($file['items']) && isset($file['type'])) {
                $this->_convertFsoToZipFiles($file, $relative . $name . '/');
            }
        }
        return $zipFiles;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function canRename($data)
    {
        $templatesDir = JPATH_SITE . '/templates/';
        $themeName = strtolower($data['themeName']);
        if (file_exists($templatesDir . $themeName)) {
            return $this->_response(false);
        } else {
            return $this->_response(true);
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getThemeZip($data)
    {
        $userThemeName  = $data['themeName'];
        $templateName = $data['template'];
        $includeEditor = $data['includeEditor'] == 'false' ? false : true;

        $originalDir = JPATH_SITE . '/templates/' . $templateName;

        $archive = new Archive();

        return $archive->getArchive($originalDir, $templateName, $userThemeName, $includeEditor);
    }

    /**
     * @param $data
     * @return string
     */
    public function downloadTheme($data) {
        $templateObject = $this->getTemplateObject($data['templateId']);
        $originalDir = JPATH_SITE . '/templates/' . $templateObject->element;
        $archive = new Archive();
        return $archive->getArchive($originalDir, $templateObject->name, $templateObject->name, true);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function uploadImage($data)
    {
        $filename = isset($data['filename']) ? $data['filename'] : '';

        if ('' === $filename) {
            $result = array(
                'status' => 'error',
                'message' => 'Empty file name'
            );
        } else {
            $templateName = $data['template'];
            $themeDir = JPATH_SITE . '/templates/' . $templateName;
            $isContent = isset($data['isContent']) && $data['isContent'] == true;
            $uploadPath = $themeDir . '/editor/images/designer/' . $filename;
            if ($isContent) {
                $imgDir = dirname(JPATH_BASE) . '/images';
                $contentDir = $imgDir . '/editor-content';
                if (!file_exists($contentDir))
                    Helper::createDir($contentDir);
                $uploadPath = $contentDir . '/' . $filename;
            }

            $isLast = isset($data['last']) ? $data['last'] : '';
            $result = $this->_uploadFileChunk($uploadPath, $isLast);

            if ($result['status'] === 'done') {
                $current = dirname(JURI::current()) . '/';
                $root = dirname(dirname(dirname($current)));
                $result['url'] = !$isContent ? $root . '/templates/' . $templateName . '/editor/images/designer/' . $filename :
                    $root . '/images/editor-content/' . $filename;
            }
        }

        return $this->_response($result);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function uploadTheme($data)
    {
        $filename = isset($data['filename']) ? $data['filename'] : '';
        $desThemesFolder = 'themes/';

        if ('' === $filename) {
            $result = array(
                'status' => 'error',
                'message' => 'Empty file name'
            );
        } else {
            $templateName = $data['template'];
            $themeDir = JPATH_SITE . '/templates/' . $templateName;
            $uploadPath = $themeDir . '/editor/' .  $desThemesFolder . $filename;

            $isLast = isset($data['last']) ? $data['last'] : '';
            $result = $this->_uploadFileChunk($uploadPath, $isLast);

            if ($result['status'] === 'done') {
                try {
                    $result = $this->_installTheme($uploadPath);
                } catch(Exception $e) {
                    $result = array(
                        'status' => 'error',
                        'message' => $e->getMessage()
                    );
                }
                Helper::removeDir($themeDir . '/editor/' .  $desThemesFolder);
            }
        }

        return $this->_response($result);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function copyTheme($data)
    {
        $id = $data['templateId'];
        $sourceThemeName = '';
        $themeNames = array();
        $items = $this->_getThemesList();
        foreach($items as $item) {
            if ($id == $item->id) {
                $sourceThemeName = $item->name;
            }
            $themeNames[] = $item->element;
        }
        if ('' === $sourceThemeName)
            trigger_error('Source theme not found', E_USER_ERROR);

        $newThemeName = $data['newThemeName'];
        if ('' === $newThemeName)
            $newThemeName = $sourceThemeName;
        $newThemeName = $this->_getNewName($newThemeName, $themeNames);

        $lowerOldThemeName = strtolower($sourceThemeName);
        $lowerNewThemeName = strtolower($newThemeName);

        $sourceThemeDir = JPATH_SITE . '/templates/' . $lowerOldThemeName;
        $newThemeDir = JPATH_SITE . '/templates/' . $lowerNewThemeName;
        Helper::copyDir($sourceThemeDir, $newThemeDir);

        // manifest correction
        $manifest = $newThemeDir . '/templateDetails.xml';
        $content = Helper::readFile($manifest);
        $xml = simplexml_load_string($content);
        $xml->name = $newThemeName;
        $path = $xml->config->fields['addfieldpath'];
        $xml->config->fields['addfieldpath'] = str_replace($lowerOldThemeName, $lowerNewThemeName, $path);
        foreach($xml->languages->language as $node) {
            $language = $node[0];
            $node[0] = str_replace($lowerOldThemeName, $lowerNewThemeName, $language);
        }
        // Save dom to xml file
        if (class_exists('DOMDocument')) {
            $dom = new DOMDocument('1.0', 'utf-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml->asXML());
            $data = $dom->saveXML();
        } else {
            $data = $xml->asXML();
        }
        Helper::writeFile($manifest, $data);
        // translation file correction
        $translateDir = $newThemeDir . '/language/en-GB/';
        Helper::renameFile($translateDir . 'en-GB.tpl_' . $lowerOldThemeName . '.ini',
            $translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.ini');
        if (file_exists($translateDir . 'en-GB.tpl_' . $lowerOldThemeName . '.sys.ini')) {
            Helper::renameFile($translateDir . 'en-GB.tpl_' . $lowerOldThemeName . '.sys.ini',
                $translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.sys.ini');
            $content = Helper::readFile($translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.sys.ini');
            $content = str_replace(strtoupper($lowerOldThemeName), strtoupper($lowerNewThemeName), $content);
            Helper::writeFile($translateDir . 'en-GB.tpl_' . $lowerNewThemeName . '.sys.ini', $content);
        }
        //Changes the theme in database
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('params');
        $query->from('#__template_styles');
        $query->where('template=' . $db->quote($lowerOldThemeName));
        $db->setQuery($query);
        $params = $db->loadResult();

        $query = $db->getQuery(true);
        $query->insert('#__template_styles');
        $query->set('template=' . $db->quote($lowerNewThemeName));
        $query->set('client_id=0');
        $query->set('home=0');
        $query->set('title=' . $db->quote($newThemeName . ' - Default'));
        $query->set('params=' . $db->quote($params));
        $db->setQuery($query);
        $db->query();

        $query = $db->getQuery(true);
        $query->select('manifest_cache, params, name');
        $query->from('#__extensions');
        $query->where('type=' . $db->quote('template')  . ' and element=' . $db->quote($lowerOldThemeName));
        $db->setQuery($query);
        $ret = $db->loadAssoc();
        $ext_cache = $ret['manifest_cache'];
        $ext_params = $ret['params'];
        $ext_name = $ret['name'];

        $query = $db->getQuery(true);
        $query->insert('#__extensions');
        $query->set('name=' . $db->quote($newThemeName));
        $query->set('type=' . $db->quote('template'));
        $query->set('element=' . $db->quote($lowerNewThemeName));
        $query->set('client_id=0');
        $query->set('enabled=1');
        $query->set('access=1');
        $query->set('protected=0');
        $query->set('manifest_cache=' . $db->quote(str_replace($ext_name, $newThemeName, $ext_cache)));
        $query->set('params=' . $db->quote($ext_params));
        $db->setQuery($query);
        $db->query();

        return $this->_response('copied');
    }

    /**
     * @param $data
     * @return mixed
     */
    public function makeThemeAsActive($data)
    {
        $themeId = $data['themeId'];
        if ($themeId) {
            $templateObject = $this->getTemplateObject($themeId);
            $styleId = Config::getStyleObject($templateObject->element)->id;
        } else {
            $styleId = Config::getStyleObject()->id;
        }
        // Include dependancies
        jimport('joomla.application.component.controller');
        // Declaration contstants
        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_templates');
        define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_templates');
        define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_templates');
        // Set of variables for controller
        JRequest::setVar('task', 'styles.setDefault');
        JRequest::setVar('cid', $styleId);
        JRequest::setVar(JSession::getFormToken(), '1');
        // Theme activation
        $controller = JControllerLegacy::getInstance('Templates', array('base_path' => JPATH_COMPONENT));
        $controller->execute(JRequest::getCmd('task'));
        return $this->_response('activated');
    }

    public function getEditableContent($data)
    {
        if (!isset($data['contentId'])) {
            return;
        }

        $result = array('content' => '', 'model' => array());

        list($type, $id) = explode("-", $data['contentId']);
        if ('article' === $type) {
            $article = JTable::getInstance("content");
            $text = '';
            if ($article->load($id)) {
                $result['content'] = $article->introtext;
                if ($article->fulltext)
                    $result['content'] .= '<hr id="system-readmore" />' . $article->fulltext;
            }
        }

        $templateName = $data['template'];
        $themeDir = JPATH_SITE . '/templates/' . $templateName;
        $sh = $themeDir . '/editor/plugins/content/lib/Shortcodes.php';
        if (file_exists($sh)) {
            require_once $themeDir . '/editor/plugins/content/lib/Shortcodes.php';
            $result['content'] = DesignerShortcodes::applyFilters($result['content']);
        }
        return $this->_response($result);
    }


    public function putEditableContent($data)
    {
        $info = $this->_getChunkInfo($data);

        $chunk = new Chunk();
        if (!$chunk->save($info)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400);
            return;
        }
        if (!$chunk->last()) {
            return array('result' => 'processed');
        }

        $result = $chunk->complete();
        if ($result['status'] === 'done') {
            $data = json_decode($result['data'], true);
        } else {
            $result['result'] = 'error';
            return $this->_response(array($result));
        }

        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        $params = JComponentHelper::getParams('com_media');
        $imgDir = JPATH_ROOT . '/' . $params->get('file_path', 'images');
        $contentDir = JPath::clean(implode('/', array($imgDir, 'editor-content')));
        if (!file_exists($contentDir))
            Helper::createDir2($contentDir);
        $root = dirname(dirname(dirname(dirname(JURI::current())))) . '/';
        $imgDirUrl = $root . 'images/editor-content/';

        if (is_array($data) && count($data) > 0) {
            foreach($data as $value) {
                $contentId = $value['contentId'];
                if ($contentId) {
                    list($type, $articleId) = explode("-", $contentId);
                    $content = $value['content'];
                    $images = $value['images'];
                    if (is_array($images) && count($images) > 0) {

                        if (!file_exists($contentDir))
                            Helper::createDir($contentDir);
                        $guids = array();
                        $values = array();
                        foreach ($images as $id => $data) {
                            if ($data) {
                                $validName = preg_replace('/[^a-z0-9_\.]/i', '', $id);
                                $guids[] = $id;
                                $values[] = $imgDirUrl . $validName;
                                $filePath = $contentDir . '/' . $validName;
                                Helper::writeFile($filePath, base64_decode($data));
                            }
                        }
                        $content = str_replace($guids, $values, $content);
                    }
                    $content = preg_replace('#src=("|\')url\(([^\)]+)\)#', 'src=$1$2', $content);
                    if ('article' === $type) {
                        $article = JTable::getInstance("content");
                        if ($article->load($articleId)) {
                            $parts = explode('<hr id="system-readmore" />', $content);
                            $article->introtext = $parts[0];
                            if (count($parts) > 1)
                                $article->fulltext = $parts[1];
                            if (!$article->check())
                                trigger_error($article->getError(), E_USER_ERROR);
                            if (!$article->store())
                                trigger_error($article->getError(), E_USER_ERROR);
                            if (!$article->checkin())
                                trigger_error($article->getError(), E_USER_ERROR);
                        }
                    }
                }
            }
        }

        return $this->_response(array('result' => 'done'));
    }

    /**
     * @param $data
     * @return mixed
     */
    public function getCmsContent($data)
    {
        $timeLogging = LoggingTime::getInstance();
        $contentData = $data['data'];

        $timeLogging->start('[PHP] Get Cms Content');

        $return = array();
        $root = dirname(dirname(dirname(dirname(JURI::current())))) . '/';
        foreach($contentData as $dataType => $options) {
            $return[$dataType] = array('contentJson' => array());
            $ids = array_key_exists('ids', $options) ? $options['ids'] : null;
            if ($ids == null)
                continue;
            $limit = array_key_exists('limit', $options) ? $options['limit'] : 1;
            $searchString = array_key_exists('searchString', $options) ? $options['searchString'] : '';

            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('*');
            $query->from('#__content');
            $ids = implode(',', $ids);
            $query->where('id IN (' . $ids . ')');
            if ($searchString) {
                $query->where('title like "%' . $searchString . '%"');
            }
            $db->setQuery($query, 0, (int)$limit);
            $list = $db->loadObjectList();

            $result = array();

            foreach($list as $item) {
                $images = json_decode($item->images, true);
                $image = $images['image_intro'] ? (strpos($images['image_intro'], $root) == false ? $root : '') . $images['image_intro'] : '';
                $fullImage = $images['image_fulltext'] ? (strpos($images['image_fulltext'], $root) == false ? $root : '')  . $images['image_fulltext'] : '';
                $result[$item->id] = array(
                    "caption" => $item->title,
                    "content" => $item->introtext,
                    "excerpt" => $item->fulltext,
                    "name" => $item->alias,
                    "fullImage" => $fullImage,
                    "image" => $image,
                    "date" => $item->created
                );
            }
            $return[$dataType] = array('contentJson' => $result);
        }
        $timeLogging->end('[PHP] Get Cms Content');
        $timeLogging->end('[PHP] Joomla start of work');
        return $this->_response(array('logs' => $timeLogging->getLog(), 'data' => $return));
    }

    /**
     * @param $property
     * @param array $a
     * @param string $default
     * @return string
     */
    public function getPropertyValue($property, $a = array(), $default = ''){
        if (array_key_exists($property, $a))
            return $a[$property];
        return $default;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function putCmsContent($data)
    {
        $timeLogging = LoggingTime::getInstance();
        $contentData = $data['data'];
        $template = $data['template'];
        $themeDir = JPATH_SITE . '/templates/' . $template;

        $timeLogging->start('[PHP] Put Cms Content');

        // Load translations
        $app = JFactory::getApplication('administrator');
        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_content');
        define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_content');
        define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_content');
        $lang = JLanguage::getInstance('en-GB');
        $lang->load('lib_joomla', JPATH_ADMINISTRATOR);
        $lang->load('com_content', JPATH_BASE, null, false, true) ||
        $lang->load('com_content', JPATH_COMPONENT, null, false, true);
        if (version_compare(JVERSION, '3.0', '<')) {
            JFactory::$language = $lang;
        } else {
            $app->loadLanguage($lang);
            JFactory::$language = $app->getLanguage();
        }

        foreach($contentData as $kind => $options) {
            $contentJson = $options['contentJson'];
            $templateType = $options['templateName'];
            $putMethod = array_key_exists('putMethod', $options) ? strtolower($options['putMethod']) : '';
            if (!$putMethod)
                continue;
            $idMap = array();

            require_once $themeDir . '/library/' . 'Designer.php';
            Designer::load('Designer_Data_Mappers');

            $callback = array();
            $callback[] = $this;
            $callback[] = '_error';
            Designer_Data_Mappers::errorCallback($callback);

            $categories = Designer_Data_Mappers::get('category');
            $content = Designer_Data_Mappers::get('content');

            $sampleCategoryName = 'Content / Sample Category';
            $categoryList = $categories->find(array('title' => $sampleCategoryName));
            
            if (count($categoryList) == 0) {
                $category = $categories->create();
                $category->title = $sampleCategoryName;
                $category->extension = 'com_content';
                $category->metadata = $this->_paramsToString(array('robots' => '', 'author' => '', 'tags' => ''));
                $status = $categories->save($category);
                if (is_string($status))
                trigger_error($status, E_USER_ERROR);
                $categoryId = $category->id;
            } else {
                $categoryId = $categoryList[0]->id;
            }

            foreach($contentJson as $id => $postData) {
                $article = $content->create();
                $article->catid = $categoryId;
                $article->title = $this->getPropertyValue('caption', $postData, 'post_' . round(microtime(true)));
                $date = new JDate();
                $article->alias = $date->format('Y-m-d-H-i-s') . '-' . $id;
                $article->introtext = $this->getPropertyValue('content', $postData, '');
                $article->fulltext = $this->getPropertyValue('excerpt', $postData, '');
                $images = json_decode('{"image_intro":"","float_intro":"","image_intro_alt":"","image_intro_caption":"","image_fulltext":"","float_fulltext":"","image_fulltext_alt":"","image_fulltext_caption":""}');
                $images->image_intro = preg_replace('#url\((.*)\)#', '$1', $this->getPropertyValue('image', $postData, ''));
                $images->image_fulltext = $this->getPropertyValue('fullImage', $postData, $images->image_intro);
                $article->images = json_encode($images);
                $article->attribs = $this->_paramsToString(array
                (
                    'show_title' => '',
                    'link_titles' => '',
                    'show_intro' => '',
                    'show_category' => '',
                    'link_category' => '',
                    'show_parent_category' => '',
                    'link_parent_category' => '',
                    'show_author' => '',
                    'link_author' => '',
                    'show_create_date' => '',
                    'show_modify_date' => '',
                    'show_publish_date' => '',
                    'show_item_navigation' => '',
                    'show_icons' => '',
                    'show_print_icon' => '',
                    'show_email_icon' => '',
                    'show_vote' => '',
                    'show_hits' => '',
                    'show_noauth' => '',
                    'alternative_readmore' => '',
                    'article_layout' => ''
                ));
                $article->metadata = $this->_paramsToString(array('robots' => '', 'author' => '', 'rights' => '', 'xreference' => '', 'tags' => ''));
                $status = $content->save($article);
                if (is_string($status))
                    return $this->_error($status, 1);
                $idMap[$id] = $article->id;
            }

            if ($template) {
                $ids = implode(',', array_reverse($idMap));
                $styleId = $data['styleId'];
                $paramsObject = $this->_getParameters($styleId);
                $type = strtolower($kind);
                $sampleData = json_decode(isset($paramsObject['sampleData']) ? $paramsObject['sampleData'] : '', true);
                if (!$sampleData) $sampleData = array();
                if (!isset($sampleData[$type])) $sampleData[$type] = array();
                $idsToRemove = isset($sampleData[$type][$templateType]) ? $sampleData[$type][$templateType] : '';
                $sampleData[$type][$templateType] = $ids;
                $paramsObject['sampleData'] = json_encode($sampleData);
                $this->_setPatameters($styleId, $paramsObject);
            }

            $idsToRemove = explode(',', trim($idsToRemove));
            if ($putMethod === 'replace' && is_array($idsToRemove)) {
                // move to trash
                // status -2
                foreach($idsToRemove as $id) {
                    $id = (int) $id;
                    if (0 !== $id && count($content->find(array('id' => $id))) > 0) {
                        $content->delete($id);
                    }
                }
            }
        }

        $timeLogging->end('[PHP] Put Cms Content');
        $timeLogging->end('[PHP] Joomla start of work');
        return $this->_response('done - put cms content');
    }

    /**
     * @param $msg
     * @param $code
     * @return mixed
     */
    public function _error($msg, $code)
    {
        return $msg;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function removeTheme($data)
    {
        $app = JFactory::getApplication('administrator');
        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_installer');
        define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_installer');
        define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_installer');

        // Create token
        $session = JFactory::getSession();
        $token = $session::getFormToken();

        // Load translations
        $lang = JLanguage::getInstance('en-GB');
        $lang->load('lib_joomla', JPATH_ADMINISTRATOR);
        $lang->load('com_installer', JPATH_BASE, null, false, true) ||
        $lang->load('com_installer', JPATH_COMPONENT, null, false, true);

        if (version_compare(JVERSION, '3.0', '<')) {
            JFactory::$language = $lang;
        } else {
            $app->loadLanguage($lang);
            JFactory::$language = $app->getLanguage();
        }

        JRequest::setVar('task', 'manage.remove');
        JRequest::setVar('cid', $data['templateId']);
        JRequest::setVar($token, '1');

        $controller	= JControllerLegacy::getInstance('Installer');
        $controller->execute(JRequest::getCmd('task'));

        $successMessage = JText::sprintf('COM_INSTALLER_UNINSTALL_SUCCESS', JText::_('COM_INSTALLER_TYPE_TYPE_TEMPLATE'));
        $errors = array();
        $messages = $app->getMessageQueue();
        foreach($messages as $msg) {
            if ($msg['message'] === $successMessage){
                return $this->_response(array(
                    'status' => 'done',
                    'message' => 'theme removed'
                ));
            }
            if ($msg['message'])
                $errors[] = $msg['message'];
        }
        if (count($errors) > 0)
            $errorText = implode("<br />", $errors);
        else
            $errorText = 'Uninstalling template failed';
        return $this->_response(array(
            'status' => 'error',
            'message' => $errorText
        ));
    }

    /**
     * @param $data
     */
    public function importContent($data)
    {
        $id = $data['id'];
        $templateName = $data['template'];
        $themeDir = JPATH_SITE . '/templates/' . $templateName;

        require_once $themeDir . '/library/' . 'Designer.php';
        Designer::load('Designer_Data_Loader');

        $loader = new Designer_Data_Loader();
        $loader->load($themeDir . '/data/data.xml');
        $result = $loader->execute(array('action' => 'run', 'id' => $id));

        return $this->_response('imported');
    }

    /**
     * @param $path
     * @return mixed
     */
    private function _installTheme($zipFile)
    {
        if (!file_exists($zipFile)) {
            return array(
                'status' => 'error',
                'message' => 'File ' . $zipFile  . ' not found.'
            );
        }

        $info = pathinfo($zipFile);
        $suffix = isset($info['extension']) ? '.'.$info['extension'] : '';
        $fileName =  basename($zipFile, $suffix);
        $extractDir = dirname($zipFile) . '/' . $fileName;
        Helper::createDir($extractDir);

        $app = JFactory::getApplication('administrator');

        define('JPATH_COMPONENT', JPATH_BASE . '/components/com_installer');
        define('JPATH_COMPONENT_SITE', JPATH_SITE . '/components/com_installer');
        define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_installer');

        // Create token
        $session = JFactory::getSession();
        $token = $session::getFormToken();

        // Load translations
        $lang = JLanguage::getInstance('en-GB');
        $lang->load('lib_joomla', JPATH_ADMINISTRATOR);
        $lang->load('com_installer', JPATH_BASE, null, false, true) ||
        $lang->load('com_installer', JPATH_COMPONENT, null, false, true);

        if (version_compare(JVERSION, '3.0', '<')) {
            jimport('joomla.filesystem.archive');
            $result = JArchive::extract($zipFile, $extractDir);
            if ($result === false) {
                return array(
                    'status' => 'error',
                    'message' => 'Invalid type.'
                );
            }
            JRequest::setVar('installtype', 'folder');
            JRequest::setVar('task', 'install.install');
            JRequest::setVar('install_directory', $extractDir);
            // Register the language object with JFactory
            JFactory::$language = $lang;
            JRequest::setVar($token, 1, 'post');
        } else {
            try {
                JArchive::extract($zipFile, $extractDir);
            } catch (Exception $e) {
                return array(
                    'status' => 'error',
                    'message' => $e->getMessage()
                );
            }
            $app->input->set('installtype', 'folder');
            $app->input->set('task', 'install.install');
            $app->input->set('install_directory', $extractDir);
            // Register the language object with JFactory
            $app->loadLanguage($lang);
            JFactory::$language = $app->getLanguage();
            $app->input->post->set($token, 1);
        }
        $pathManifest = '';
        $subfolder = '';
        if (file_exists($extractDir . '/templateDetails.xml')) {
            $pathManifest = $extractDir . '/templateDetails.xml';
        } else {
            $handle = opendir($extractDir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $path = $extractDir . '/'. $entry;
                    if(is_dir($path) && file_exists($path . '/templateDetails.xml')) {
                        $pathManifest = $path . '/templateDetails.xml';
                        $subfolder = '/' . $entry ;
                        break;
                    }
                }
            }
        }

        if ('' == $pathManifest) {
            return array(
                'status' => 'error',
                'message' => 'Only Joomla templates are allowed.'
            );
        }

        $items = $this->_getThemesList();
        $themeNames = array();
        foreach($items as $item) {
            $themeNames[] = $item->element;
        }
        $xml = simplexml_load_string(Helper::readFile($pathManifest));
        $currentThemeName = (string)$xml->name;

        $newThemeName = $this->_getNewName($currentThemeName, $themeNames);

        if ($currentThemeName !== $newThemeName) {
            $translateDir = $extractDir . $subfolder . '/language/en-GB/';
            Helper::renameFile($translateDir . 'en-GB.tpl_' . $currentThemeName . '.ini',
                $translateDir . 'en-GB.tpl_' . $newThemeName . '.ini');
            if (file_exists($translateDir . 'en-GB.tpl_' . $currentThemeName . '.sys.ini')) {
                Helper::renameFile($translateDir . 'en-GB.tpl_' . $currentThemeName . '.sys.ini',
                    $translateDir . 'en-GB.tpl_' . $newThemeName . '.sys.ini');
                $content = Helper::readFile($translateDir . 'en-GB.tpl_' . $newThemeName . '.sys.ini');
                $content = str_replace(strtoupper($currentThemeName), strtoupper($newThemeName), $content);
                Helper::writeFile($translateDir . 'en-GB.tpl_' . $newThemeName . '.sys.ini', $content);
            }
            $xml->name = $newThemeName;
            $path = $xml->config->fields['addfieldpath'];
            $xml->config->fields['addfieldpath'] = str_replace($currentThemeName, $newThemeName, $path);
            foreach($xml->languages->language as $node) {
                $language = $node[0];
                $node[0] = str_replace($currentThemeName, $newThemeName, $language);
            }
            if (class_exists('DOMDocument')) {
                $dom = new DOMDocument('1.0', 'utf-8');
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($xml->asXML());
                Helper::writeFile($pathManifest, $dom->saveXML());
            } else {
                Helper::writeFile($pathManifest, $xml->asXML());
            }
        }
        // Execute installing
        $controller	= JControllerLegacy::getInstance('Installer');
        $controller->execute(JRequest::getCmd('task'));

        $messages = $app->getMessageQueue();

        $successMessage = JText::sprintf('COM_INSTALLER_INSTALL_SUCCESS', JText::_('COM_INSTALLER_TYPE_TYPE_TEMPLATE'));

        $errors = array();
        foreach($messages as $msg) {
            if ($msg['message'] === $successMessage) {
                return array(
                    'status' => 'done',
                    'message' => $successMessage
                );
            }
            $errors[] = $msg['message'];
        }
        if (count($errors) > 0)
            $errorText = implode("<br />", $errors);
        else
            $errorText = 'Installing template failed';

        return array(
            'status' => 'error',
            'message' => $errorText
        );
    }

    /**
     * @param $name
     * @param $existsNames
     * @return mixed
     */
    private function _getNewName($name, $existsNames)
    {
        $i = 1;
        if (preg_match('/[0-9]+$/', $name, $matches)) {
            $i = ++$matches[0];
        };
        $newName = $name;
        while(in_array(strtolower($newName), $existsNames)) {
            $newName = preg_replace('/[0-9]*$/', $i, $newName, 1);
            $i++;
        }
        return $newName;
    }

    /**
     * @return mixed
     */
    private function _getThemesList()
    {
        $db = JFactory::getDBO();
        $query	= $db->getQuery(true);
        $query->from($db->quoteName('#__extensions'));
        $query->select(array('extension_id AS id', 'name', 'element', 'client_id', 'manifest_cache'));
        $query->where('type = \'template\'');
        $query->where('client_id = \'0\'');
        $db->setQuery($query);
        return $db->loadObjectList();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getTemplateObject($id)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from('#__extensions');
        $query->where('extension_id=\'' . $id . '\'');
        $db->setQuery($query);
        return $db->loadObject();
    }

    /**
     * @param $name
     * @return bool
     */
    public function themeIsActive($template)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('home');
        $query->from('#__template_styles');
        $query->where('template=\'' . $template . '\'');
        $query->where('home=1');
        $db->setQuery($query);
        $list = $db->loadAssocList();
        return count($list) > 0;
    }

    /**
     * @param $uploadPath
     * @param $isLast
     * @return array
     */
    private function _uploadFileChunk($uploadPath, $isLast)
    {
        if (!isset($_FILES['chunk']) || !file_exists($_FILES['chunk']['tmp_name'])) {
            return array(
                'status' => 'error',
                'message' => 'Empty chunk data'
            );
        }

        $contentRange = $_SERVER['HTTP_CONTENT_RANGE'];
        if ('' === $contentRange && '' === $isLast) {
            return array(
                'status' => 'error',
                'message' => 'Empty Content-Range header'
            );
        }

        $rangeBegin = 0;

        if ($contentRange) {
            $contentRange = str_replace('bytes ', '', $contentRange);
            list($range, $total) = explode('/', $contentRange);
            list($rangeBegin, $rangeEnd) = explode('-', $range);
        }

        $tmpPath = JPATH_SITE . '/tmp/' . basename($uploadPath);
        Helper::createDir(dirname($tmpPath));

        $f = fopen($tmpPath, 'c');

        if (flock($f, LOCK_EX)) {
            fseek($f, (int) $rangeBegin);
            fwrite($f, Helper::readFile($_FILES['chunk']['tmp_name']));

            flock($f, LOCK_UN);
            fclose($f);
        }

        if ($isLast) {
            if (file_exists($uploadPath)) {
                Helper::deleteFile($uploadPath);
            }
            Helper::createDir(dirname($uploadPath));
            Helper::renameFile($tmpPath, $uploadPath);

            return array(
                'status' => 'done'
            );
        } else {
            return array(
                'status' => 'processed'
            );
        }
    }

    /**
     * @param $mask
     * @param $flags
     * @return array
     */
    private function _getFiles($mask, $flags)
    {
        $files = glob($mask, $flags);
        if (!is_array($files)) {
            $files = array();
        }
        
        $bitwiseOrFlags = 0;
        if (defined('GLOB_ONLYDIR') && defined('GLOB_NOSORT'))
            $bitwiseOrFlags = GLOB_ONLYDIR | GLOB_NOSORT;
            
        $subdirs = glob(dirname($mask) . '/*', $bitwiseOrFlags);
        if (is_array($subdirs)) {
            foreach ($subdirs as $dir)
            {
                $files = array_merge($files, $this->_getFiles($dir . '/' . basename($mask), $flags));
            }
        }

        return $files;
    }

    /**
     * @param $data
     * @return array
     */
    private function _getChunkInfo($data)
    {
        return array(
            'id' => isset($data['id']) ? $data['id'] : '',
            'content' =>  isset($data['content']) ? $data['content'] : '',
            'current' =>  isset($data['current']) ? $data['current'] : '',
            'total' =>  isset($data['total']) ? $data['total'] : '',
            'encode' => !empty($data['encode']),
            'blob' => !empty($data['blob']),
            'zip'  => !empty($data['zip'])
        );
    }

    /**
     * @param $themeId
     * @param $params
     */
    private function _setPatameters($themeId, $params)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('params')->from('#__template_styles')->where('id=' . $query->escape($themeId));
        $db->setQuery($query);
        $parameters = $this->_stringToParams($db->loadResult());

        foreach ($params as $key => $value)
            $parameters[$key] = $value;

        $query = $db->getQuery(true);
        $query->update('#__template_styles')->set(
            $db->quoteName('params') . '=' .
                $db->quote($this->_paramsToString($parameters))
        )->where('id=' . $query->escape($themeId));

        $db->setQuery($query);
        $db->query();
    }

    /**
     * @param $themeId
     * @return mixed
     */
    private function _getParameters($themeId)
    {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('params')->from('#__template_styles')->where('id=' . $query->escape($themeId));
        $db->setQuery($query);
        return $this->_stringToParams($db->loadResult());
    }

    /**
     * @param $params
     * @return mixed
     */
    private function _paramsToString($params)
    {
        $registry = new JRegistry();
        $registry->loadArray($params);
        return $registry->toString();
    }

    /**
     * @param $string
     * @return mixed
     */
    private function _stringToParams($string)
    {
        $registry = new JRegistry();
        $registry->loadString($string);
        return $registry->toArray();
    }

    /**
     * @param $result
     * @return mixed
     */
    private function _response($result)
    {
        if (is_string($result)) {
            $result = array('result' => $result);
        }
        return json_encode($result);
    }
}