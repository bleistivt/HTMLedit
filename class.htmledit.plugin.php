<?php

class HTMLeditPlugin extends Gdn_Plugin {

    // Override the master view
    public function base_beforeFetchMaster_handler($sender) {
        $args = &$sender->EventArguments;
        // If /vanilla/getmaster was called, echo out the master view.
        if (isset($this->getMaster)) {
            safeHeader('Content-Type: text/plain', true);
            readfile($args['MasterViewPath']);
            exit();
        }

        $isDefault = strpos($args['MasterViewPath'], 'default.master') !== false;

        if ($isDefault && $this->enabled(isMobile())) {
            $args['MasterViewPath'] = $this->master(isMobile()) ?: $args['MasterViewPath'];
        }
    }


    // Adds the editor link to the dashboard
    public function base_getAppSettingsMenuItems_handler($sender, $args) {
        $args['SideMenu']->addLink(
            'Appearance',
            Gdn::translate('HTML Editor'),
            'settings/htmledit',
            'Garden.Settings.Manage'
        );
    }


    // The editor page
    public function settingsController_htmledit_create($sender, $mobile = ''){
        $sender->permission('Garden.Settings.Manage');
        $sender->setHighlightRoute('settings/htmledit');

        $mobile = $mobile == 'mobile';

        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setValue('Enabled', $this->enabled($mobile));

            if ($this->master($mobile)) {
                $sender->Form->setValue('Master', file_get_contents($this->master($mobile)));
            } else {
                $sender->addDefinition('HTMLedit.initEditor', true);
            }

        } else {
            $master = $sender->Form->getValue('Master', '');
            $this->master($mobile, $master);
            $this->enabled($mobile, $sender->Form->getValue('Enabled'));

            $matchAssets = '/{asset name=((?:\'|")(?:Head|Content|Foot)(?:\'|"))/';
            if (preg_match_all($matchAssets, $master) < 3) {
                $sender->Form->addError('Warning: Your master view should at least contain the Head, Content and Foot assets to work.');
            }

            $sender->informMessage(Gdn::translate('Your changes have been saved.'));
        }

        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js');
        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/mode-smarty.js');
        $sender->addJsFile('//cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/theme-crimson_editor.js');
        $sender->addJsFile('htmledit.js', 'plugins/HTMLedit');

        $sender->addDefinition('HTMLedit.loadMessage', Gdn::translate("Load default master view into the editor?\nUnsaved changes will be lost."));
        $sender->addDefinition('HTMLedit.leaveMessage', Gdn::translate('Do you really want to leave? Your changes will be lost.'));

        $sender->title(Gdn::translate('HTML Editor'));
        $sender->render('editor', '', 'plugins/HTMLedit');
    }


    // Spits out the master view to be loaded into the editor
    public function vanillaController_getMaster_create($sender, $mobile = '') {
        $sender->permission('Garden.Settings.Manage');

        $this->getMaster = true;
        // Set the mobile state and theme.
        isMobile($mobile == 'mobile');
        $sender->Theme = Gdn::themeManager()->currentTheme();

        // Trick the controller into fetching the master view.
        $sender->render('blank', 'utility', 'dashboard');
    }


    // Disables the modified master view when the theme is switched
    public function settingsController_afterEnableTheme_handler($sender, $args) {
        if ($args['ThemeName'] == Gdn::config('Garden.Theme')) {
            $this->enabled(false, false);
        } elseif ($args['ThemeName'] == Gdn::config('Garden.MobileTheme')) {
            $this->enabled(true, false);
        }
    }


    // Writes to the master view or returns its path
    private function master($mobile = false, $content = false) {
        $path = PATH_UPLOADS.'/htmledit/';
        $file = $path.($mobile ? 'mobile' : 'desktop').'.master.tpl';

        if ($content === false) {
            return file_exists($file) ? $file : false;
        }

        if (!file_exists($path)) {
            mkdir($path);
        }
        file_put_contents($file, $content);
    }


    // Get/set the plugins configuration for mobile/desktop
    private function enabled($mobile, $set = null) {
        $key = 'HTMLedit.'.($mobile ? 'Mobile' : 'Desktop').'.Enabled';
        if (!is_null($set)) {
            saveToConfig($key, $set);
        }
        return Gdn::config($key, true);
    }

}
