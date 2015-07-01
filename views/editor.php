<?php if (!defined('APPLICATION')) exit();

$mobile = (val(0, $this->RequestArgs) == 'mobile'); ?>

<h1><?php echo t('HTML Editor'); ?></h1>
<?php echo $this->Form->errors(); ?>
<div class="Tabs">
    <ul>
        <li<?php if (!$mobile) echo ' class="Active"'; ?>>
            <?php echo anchor(t('Desktop Theme'), '/dashboard/settings/htmledit'); ?>
        </li>
        <li<?php if ($mobile) echo ' class="Active"'; ?>>
            <?php echo anchor(t('Mobile Theme'), '/dashboard/settings/htmledit/mobile'); ?>
        </li>
    </ul>
</div>
<ul>
    <li>
        <div id="AceEditor" style="height:550px;border:solid #82bddd;border-width: 1px 0;display:none;"></div>
    </li>
</ul>
<?php echo $this->Form->open(array('id' => 'Form_HTMLedit')); ?>
<ul>
    <li id="NoJsForm">
        <?php echo $this->Form->textBox('Master', array('MultiLine' => true, 'class' => 'InputBox WideInput')); ?>
    </li>
    <li>
        <?php echo anchor(
            t('Load this themes default.master.tpl into the editor'),
            'vanilla/getmaster'.($mobile ? '/mobile' : ''),
            'LoadMaster'
        ); ?>
    </li>
    <li>
        <?php echo $this->Form->checkBox('Enabled', 'Enable'); ?>
    </li>
    <li>
        <div class="Message AlertMessage"><?php echo t('Note: Themes with a default.master.php are not supported.'); ?></div>
    </li>
</ul>
<?php
    echo $this->Form->button('Save', array('class' => 'Button HTMLeditSave'));
    echo $this->Form->close();
