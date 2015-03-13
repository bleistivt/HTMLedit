<?php if (!defined('APPLICATION')) exit();

$PluginInfo['HTMLedit'] = array(
    'Name' => 'HTMLedit',
    'Description' => 'Adds the ability to edit the default.master.tpl of your template through the dashboard.',
    'Version' => '0.4',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'Author' => 'Bleistivt',
    'AuthorUrl' => 'http://bleistivt.net',
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/dashboard/settings/htmledit',
    'MobileFriendly' => true
);

class HTMLeditPlugin extends Gdn_Plugin {

    private $getMaster = false;

    // Override the master view
    public function Base_BeforeFetchMaster_Handler($Sender) {
        $MasterViewPath = &$Sender->EventArguments['MasterViewPath'];
        // If /vanilla/getmaster was called, echo out the master view
        if ($this->getMaster) {
            safeHeader('Content-Type: text/plain', true);
            readfile($MasterViewPath);
            exit();
        }
        if (strpos($MasterViewPath, 'default.master') === false) {
            return;
        }
        $Media = IsMobile() ? 'mobile' : 'desktop';
        $MasterView = PATH_UPLOADS.'/htmledit/'.$Media.'.master.tpl';
        if (C('HTMLedit.'.ucfirst($Media).'.Enabled', true) && file_exists($MasterView)) {
            $MasterViewPath = $MasterView;
        }
    }

    // Adds the editor link to the dashboard
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->AddLink('Appearance', T('HTML Editor'), 'settings/htmledit', 'Garden.Settings.Manage');
    }

    // The editor page
    public function SettingsController_HTMLedit_Create($Sender){
        $Sender->Permission('Garden.Settings.Manage');
        $Mobile = (val(0, $Sender->RequestArgs) == 'mobile');
        $Media = $Mobile ? 'mobile' : 'desktop';
        $ConfEnabledString = 'HTMLedit.'.ucfirst($Media).'.Enabled';
        $File = PATH_UPLOADS.'/htmledit/'.$Media.'.master.tpl';
        $Session = Gdn::Session();

        if ($Sender->Form->AuthenticatedPostBack() === FALSE) {
            $Sender->Form->SetValue('Enabled', C($ConfEnabledString, true));
            if (file_exists($File)) {
                $Sender->Form->SetValue('Master', file_get_contents($File));
            } else {
                $Sender->AddDefinition('HTMLedit.initEditor', true);
            }
        } else {
            $FormValues = $Sender->Form->FormValues();
            $Master = val('Master', $FormValues, '');
            $this->WriteMaster($Master, $Mobile);
            SaveToConfig($ConfEnabledString, val('Enabled', $FormValues));
            $Sender->InformMessage(T('Your changes have been saved.'));
            if (preg_match_all('/{asset name=((?:\'|")(?:Head|Content|Foot)(?:\'|"))/', $Master) < 3) {
                $Sender->Form->AddError('Warning: Your master view should at least contain the Head, Content and Foot assets to work.');
            }
        }
        $Sender->AddSideMenu('settings/htmledit');
        $Sender->Title(T('HTML Editor'));

        $Sender->AddJsFile('ace.js', 'plugins/HTMLedit');
        $Sender->AddJsFile('htmledit.js', 'plugins/HTMLedit');
        $Sender->AddDefinition('HTMLedit.loadMessage', T("Load default master view into the editor?\nUnsaved changes will be lost."));
        $Sender->AddDefinition('HTMLedit.leaveMessage', T('Do you really want to leave? Your changes will be lost.'));

        $Sender->Render($this->GetView('editor.php'));
    }

    // Spits out the master view to be loaded into the editor
    public function VanillaController_GetMaster_Create($Sender) {
        $Mobile = (val(0, $Sender->RequestArgs) == 'mobile');
        $Sender->Permission('Garden.Settings.Manage');
        $this->getMaster = true;
        // Set the mobile state and theme
        IsMobile($Mobile);
        $Sender->Theme = Gdn::ThemeManager()->CurrentTheme();
        // Trick the controller into fetching the master view
        $Sender->Render('Blank', 'Utility', 'Dashboard');
    }

    private function WriteMaster($Content, $Mobile = false) {
        $Path = PATH_UPLOADS.'/htmledit/';
        if (!file_exists($Path)) {
            mkdir($Path);
        }
        file_put_contents($Path.($Mobile ? 'mobile' : 'desktop').'.master.tpl', $Content);
    }
    
    // Disable the modified master view when the theme is switched
    public function SettingsController_AfterEnableTheme_Handler($Sender) {
        if ($Sender->EventArguments['ThemeName'] == C('Garden.Theme')) { 
            SaveToConfig('HTMLedit.Desktop.Enabled', false);
        } elseif ($Sender->EventArguments['ThemeName'] == C('Garden.MobileTheme')) {
            SaveToConfig('HTMLedit.Mobile.Enabled', false);
        }
    }

}
