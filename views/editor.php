<?php if (!defined('APPLICATION')) exit();

$Mobile = (val(0, $this->RequestArgs) == 'mobile'); ?>

<h1><?php echo T('HTML Editor'); ?></h1>
<?php echo $this->Form->Errors(); ?>
<div class="Tabs">
    <ul>
        <li<?php if (!$Mobile) echo ' class="Active"'; ?>>
            <?php echo Anchor(T('Desktop Theme'), '/dashboard/settings/htmledit'); ?>
        </li>
        <li<?php if ($Mobile) echo ' class="Active"'; ?>>
            <?php echo Anchor(T('Mobile Theme'), '/dashboard/settings/htmledit/mobile'); ?>
        </li>
    </ul>
</div>
<ul>
    <li>
        <div id="AceEditor" style="height:550px;border:solid #82bddd;border-width: 1px 0;display:none;"></div>
    </li>
</ul>
<?php echo $this->Form->Open(array('id' => 'Form_HTMLedit')); ?>
<ul>
    <li id="NoJsForm">
        <?php echo $this->Form->TextBox('Master', array('MultiLine' => TRUE, 'class' => 'InputBox WideInput')); ?>
    </li>
    <li>
        <?php echo Anchor(
            T('Load this themes default.master.tpl into the editor'),
            'vanilla/getmaster'.($Mobile ? '/mobile' : ''),
            'LoadMaster'
        ); ?>
    </li>
    <li>
        <?php echo $this->Form->CheckBox('Enabled', 'Enable'); ?>
    </li>
    <li>
        <div class="Message AlertMessage"><?php echo T('Note: Themes with a default.master.php are not supported.'); ?></div>
    </li>
</ul>
<?php
    echo $this->Form->Button('Save', array('class' => 'Button HTMLeditSave'));
    echo $this->Form->Close();
