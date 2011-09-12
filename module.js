
M.plagiarism_moss = {};

M.plagiarism_moss.Y = {};

M.plagiarism_moss.confirm_button_clicked = function(e) {
    e.preventDefault();
    var Y = M.plagiarism_moss.Y;

    var button = e.currentTarget;
    var new_button = button.cloneNode(true);  // a temp button used to construct outerHTML

    var current_html = button.get('outerHTML');
    var link = button.get('href');
    var id = button.get('id');

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
    new_button.set('href', uri + '?' + newdata);
    new_button.set('innerHTML', confirm_html);

    // bind io events
    Y.on('io:success', M.plagiarism_moss.iocomplete, Y, [id, new_button.get('outerHTML')]);
    Y.on('io:failure', M.plagiarism_moss.iofailure, Y, [id, current_html]);

    data += '&ajax=1';
    var cfg = {
        method : 'GET',
        data : data
    }
    Y.io(uri, cfg);

    var buttons = Y.all('#'+id);
    var updating_html = M.plagiarism_moss.updating_html.replace("TO_BE_FILLED", id);
    buttons.set('outerHTML', updating_html);
}

M.plagiarism_moss.iocomplete = function(transactionid, response, arguments) {
    var Y = M.plagiarism_moss.Y;
    var id = arguments[0];
    var html = arguments[1];

    var buttons = Y.all('#'+id);
    buttons.set('outerHTML', html);

    // After setting outerHTML, new buttons need to be rebinded
    M.plagiarism_moss.bind_buttons(id);
}

M.plagiarism_moss.iofailure = function(transactionid, response, arguments) {
    M.plagiarism_moss.iocomplete(transactionid, response, arguments);
    alert('Network error');
}

M.plagiarism_moss.init = function(Y, unconfirmed_html, confirmed_html, updating_html) {
    M.plagiarism_moss.Y = Y;
    M.plagiarism_moss.unconfirmed_html = unconfirmed_html;
    M.plagiarism_moss.confirmed_html = confirmed_html;
    M.plagiarism_moss.updating_html = updating_html;

    M.plagiarism_moss.Y.on("click", M.plagiarism_moss.confirm_button_clicked, ".confirmbutton");
};

M.plagiarism_moss.bind_buttons = function(id) {
    M.plagiarism_moss.Y.on("click", M.plagiarism_moss.confirm_button_clicked, "#"+id);
}
