<?php if (!defined('APPLICATION')) exit();

$mobile = (val(0, $this->RequestArgs) == 'mobile');

echo heading($this->title());

?>

<div class="padded">
    <p>
        <?php echo t('Note: Themes with a default.master.php are not supported.'); ?>
    </p>
</div>

<div class="toolbar">
    <div class="btn-group filters">
        <?php
        echo anchor(
            t('Desktop Theme'),
            '/dashboard/settings/htmledit',
            'btn btn-secondary'.(!$mobile ? ' active' : '')
        );
        echo anchor(
            t('Mobile Theme'),
            '/dashboard/settings/htmledit/mobile',
            'btn btn-secondary'.($mobile ? ' active' : '')
        );
        ?>
        </li>
    </div>
</div>

<div id="AceEditor" style="height:550px;display:none;margin:0 -1.125rem;border-bottom:0.0625rem solid #e7e8e9;"></div>

<?php

echo $this->Form->open(['id' => 'Form_HTMLedit']);
echo $this->Form->errors();

?>
<ul>
    <li class="form-group" id="NoJsForm">
        <?php echo $this->Form->textBox('Master', [
            'MultiLine' => true,
            'class' => 'InputBox WideInput'
        ]); ?>
    </li>
    <li class="form-group">
        <?php echo anchor(
            t('Load this themes default.master.tpl into the editor'),
            'vanilla/getmaster'.($mobile ? '/mobile' : ''),
            'LoadMaster btn'
        ); ?>
    </li>
    <li class="form-group">
        <?php echo $this->Form->toggle('Enabled', 'Enable'); ?>
    </li>
</ul>

<div class="form-footer">
<?php echo $this->Form->button('Save', ['class' => 'Button HTMLeditSave']); ?>
</div>

<?php
echo $this->Form->close();
