<?php if (!defined('APPLICATION')) exit();

$PluginInfo['HTMLedit'] = array(
    'Name' => 'HTMLedit',
    'Description' => 'Adds the ability to edit the default.master.tpl of your template through the dashboard.',
    'Version' => '0.2',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'Author' => 'Bleistivt',
    'AuthorUrl' => 'http://bleistivt.net',
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
        $Media = IsMobile() && C('Garden.MobileTheme') ? 'mobile' : 'desktop';
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
            if (strpos($Master, '{asset name="Head"') === false
                || strpos($Master, '{asset name="Content"') === false
                || strpos($Master, '{asset name="Foot"') === false
            ) {
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

}

if (!function_exists('requestContext')) {
   /**
    * Get request context
    *
    * This method determines if current request is operating within HTTP, or
    * elsewhere such as the command line.
    *
    * @staticvar string $context
    * @return string
    */
   function requestContext() {
      static $context;
      if (is_null($context)) {
         $context = C('Garden.RequestContext', null);
         if (is_null($context)) {
            $protocol = val('SERVER_PROTOCOL', $_SERVER);
            if (preg_match('`^HTTP/`', $protocol)) {
               $context = 'http';
            } else {
               $context = $protocol;
            }
         }
         if (is_null($context)) {
            $context = 'unknown';
         }
      }
      return $context;
   }
}

if (!function_exists('safeHeader')) {
   /**
    * Context-aware call to header()
    *
    * This method is context-aware and will avoid sending headers if the request
    * context is not HTTP.
    *
    * @staticvar string $context
    * @param type $header
    * @param type $replace
    * @param type $http_response_code
    */
   function safeHeader($header, $replace = true, $http_response_code = null) {
      static $context;
      if (headers_sent()) {
         return false;
      }
      if (is_null($context)) {
         $context = requestContext();
      }

      if ($context == 'http') {
         header($header, $replace, $http_response_code);
      }
   }
}
