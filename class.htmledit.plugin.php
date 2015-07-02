<?php

$PluginInfo['HTMLedit'] = array(
    'Name' => 'HTMLedit',
    'Description' => 'Adds the ability to edit the default.master.tpl of your template through the dashboard.',
    'Version' => '0.4',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'Author' => 'Bleistivt',
    'AuthorUrl' => 'http://bleistivt.net',
    'License' => 'GNU GPL2',
    'SettingsUrl' => 'settings/htmledit',
    'MobileFriendly' => true
);

class HTMLeditPlugin extends Gdn_Plugin {

    // Override the master view
    public function base_beforeFetchMaster_handler($sender, &$args) {
        // If /vanilla/getmaster was called, echo out the master view.
        if (isset($this->getMaster)) {
            safeHeader('Content-Type: text/plain', true);
            readfile($args['MasterViewPath']);
            exit();
        }
        if (strpos($args['MasterViewPath'], 'default.master') !== false && $this->enabled(isMobile())) {
            $args['MasterViewPath'] = $this->master(isMobile()) ?: $args['MasterViewPath'];
        }
    }

    // Adds the editor link to the dashboard
    public function base_getAppSettingsMenuItems_handler($sender, &$args) {
        $args['SideMenu']->addLink('Appearance', t('HTML Editor'), 'settings/htmledit', 'Garden.Settings.Manage');
    }

    // The editor page
    public function settingsController_htmlEdit_create($sender, $mobile = ''){
        $sender->permission('Garden.Settings.Manage');
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
            if (preg_match_all('/{asset name=((?:\'|")(?:Head|Content|Foot)(?:\'|"))/', $master) < 3) {
                $sender->Form->addError('Warning: Your master view should at least contain the Head, Content and Foot assets to work.');
            }
            $sender->informMessage(t('Your changes have been saved.'));
        }
        $sender->addJsFile('ace.js', 'plugins/HTMLedit');
        $sender->addJsFile('htmledit.js', 'plugins/HTMLedit');
        $sender->addDefinition('HTMLedit.loadMessage', t("Load default master view into the editor?\nUnsaved changes will be lost."));
        $sender->addDefinition('HTMLedit.leaveMessage', t('Do you really want to leave? Your changes will be lost.'));

        $sender->title(t('HTML Editor'));
        $sender->addSideMenu('settings/htmledit');
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
        if ($args['ThemeName'] == c('Garden.Theme')) {
            $this->enabled(false, false);
        } elseif ($args['ThemeName'] == c('Garden.MobileTheme')) {
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
        return c($key, true);
    }

}
