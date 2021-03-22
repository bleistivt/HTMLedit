jQuery(($) => {
    const editor = ace.edit('AceEditor');
    let leave = false;
    editor.setTheme('ace/theme/crimson_editor');
    editor.getSession().setMode('ace/mode/smarty');
    let masterInitial = $('#Form_Master').val();
    editor.setValue(masterInitial, -1);
    if (gdn.definition('HTMLedit.initEditor', false)) {
        $.get($('.LoadMaster').attr('href'), (data) => {
            editor.setValue(data, -1);
            masterInitial = data;
        });
    }
    editor.focus();
    if (localStorage.getItem('htmleditscrollpos')) {
        try {
            const sp = JSON.parse(localStorage.getItem('htmleditscrollpos'));
            editor.moveCursorToPosition(sp.pos);
            editor.getSession().setScrollTop(sp.scroll);
        } catch (e) { }
    }
    $('#AceEditor').show();
    $('#NoJsForm').hide();
    $('.HTMLeditSave').on('click', (e) => {
        e.preventDefault();
        leave = true;
        $('#Form_Master').val(editor.getValue());
        localStorage.setItem('htmleditscrollpos', JSON.stringify({
            pos : editor.getCursorPosition(),
            scroll : editor.getSession().getScrollTop()
        }));
        $('#Form_HTMLedit').submit();
    });
    $('.LoadMaster').click(function(e) {
        e.preventDefault();
        $.get($(this).attr('href'), (data) => {
            if (editor.getValue() != masterInitial) {
                if (confirm(gdn.definition('HTMLedit.loadMessage'))) {
                    editor.setValue(data);
                }
            } else {
                editor.setValue(data);
            }
        });
    });
    $(window).on('beforeunload', () => {
        if (editor.getValue() != masterInitial && !leave) {
            return gdn.definition('HTMLedit.leaveMessage');
        }
    });
});
