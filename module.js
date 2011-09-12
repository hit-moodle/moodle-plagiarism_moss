
M.plagiarism_moss = {};

M.plagiarism_moss.Y = {};

M.plagiarism_moss.confirm_button_clicked = function(e) {
    e.preventDefault();
    var Y = M.plagiarism_moss.Y;

    var button = e.currentTarget;

    var link = button.get('href');
    var id = button.get('parentNode').get('id');

    var sp = link.split('?', 2);
    var uri = sp[0];
    var data = sp[1];

    if (data.charAt(data.length-1) == '1') { // unconfirmed now
        if (!confirm(M.util.get_string('confirmmessage', 'plagiarism_moss'))) {
            return;
        }
    }

    // bind io events
    Y.once('io:success', M.plagiarism_moss.iocomplete, Y, [id]);
    Y.once('io:failure', M.plagiarism_moss.iofailure, Y, [id, button.get('parentNode').get('innerHTML')]);

    data += '&ajax=1';
    var cfg = {
        method : 'GET',
        data : data
    }
    Y.io(uri, cfg);

    var buttons = Y.all('span#'+id);
    buttons.setContent(M.plagiarism_moss.updating_html);
}

M.plagiarism_moss.iocomplete = function(transactionid, response, arguments) {
    var Y = M.plagiarism_moss.Y;
    var id = arguments[0];

    var buttons = Y.all('span#'+id);
    buttons.setContent(response.responseText);

    M.plagiarism_moss.bind_buttons(id);
}

M.plagiarism_moss.iofailure = function(transactionid, response, arguments) {
    alert('Network error');

    var Y = M.plagiarism_moss.Y;
    var id = arguments[0];
    var old_html = arguments[1];

    var buttons = Y.all('span#'+id);
    buttons.setContent(old_html);

    M.plagiarism_moss.bind_buttons(id);
}

M.plagiarism_moss.init = function(Y, updating_html) {
    M.plagiarism_moss.Y = Y;
    M.plagiarism_moss.updating_html = updating_html;

    M.plagiarism_moss.Y.on("click", M.plagiarism_moss.confirm_button_clicked, "a.confirmbutton");
};

M.plagiarism_moss.bind_buttons = function(id) {
    M.plagiarism_moss.Y.on("click", M.plagiarism_moss.confirm_button_clicked, "span#"+id+' a.confirmbutton');
}
