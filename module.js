
M.plagiarism_moss = {};

M.plagiarism_moss.Y = {};

M.plagiarism_moss.confirm_button_clicked = function(e) {
    e.preventDefault();
    Y = M.plagiarism_moss.Y;

    var button = e.currentTarget;
    var link = button.get('href');

    var sp = link.split('?', 2);
    var uri = sp[0];
    var data = sp[1];

    if (data.charAt(data.length-1) == '1') { // unconfirmed now
        if (!confirm(M.util.get_string('confirmmessage', 'plagiarism_moss'))) {
            return;
        }
        var newdata = data.substring(0, data.indexOf('confirm=')) + 'confirm=0';
        var confirm_html = M.plagiarism_moss.confirmed_html;
    } else { // confirmed now
        var newdata = data.substring(0, data.indexOf('confirm=')) + 'confirm=1';
        var confirm_html = M.plagiarism_moss.unconfirmed_html;
    }

    data += '&ajax=1';
    var cfg = {
        method : 'GET',
        data : data
    }
    Y.io(uri, cfg);

    id = button.get('id');
    buttons = Y.all('#'+id);
    buttons.set('innerHTML', confirm_html);
    buttons.set('href', uri + '?' + newdata);
}

M.plagiarism_moss.init = function(Y, unconfirmed_html, confirmed_html) {
    M.plagiarism_moss.Y = Y;
    M.plagiarism_moss.unconfirmed_html = unconfirmed_html;
    M.plagiarism_moss.confirmed_html = confirmed_html;
    Y.on("click", M.plagiarism_moss.confirm_button_clicked, ".confirmbutton");
};
