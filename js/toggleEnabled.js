/*  Updates fields based on checkbox changes
*/
var PRFtoggleEnabled = function(cbox, id, type) {
    oldval = cbox.checked ? 0 : 1;
    var dataS = {
        "action" : "toggleEnabled",
        "id": id,
        "type": type,
        "oldval": oldval,
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: site_admin_url + "/plugins/profile/ajax.php",
        data: data,
        success: function(result) {
            cbox.checked = result.newval == 1 ? true : false;
            try {
                if (result.newval == oldval) {
                    icon = "<i class='uk-icon-exclamation-triangle'></i>&nbsp;";
                } else {
                    icon = "<i class='uk-icon-check'></i>&nbsp;";
                }
                $.UIkit.notify(icon + result.statusMessage, {timeout: 1000,pos:'top-center'});
            }
            catch(err) {
                $.UIkit.notify("<i class='uk-icon-exclamation-triangle'></i>&nbsp;" + result.statusMessage, {timeout: 1000,pos:'top-center'});
                alert(result.statusMessage);
            }
        }
    });
    return false;
};

/**
*   Not a toggle function; this updates the 3-part date field with data
*   from the datepicker.
*   @param  Date    d       Date object
*   @param  string  fld     Field Name
*   @param  integer tm_type 12- or 24-hour indicator, 0 = no time field
*/
function PRF_updateDate(d, fld, tm_type)
{
    document.getElementById(fld + "_month").selectedIndex = d.getMonth() + 1;
    document.getElementById(fld + "_day").selectedIndex = d.getDate();
    document.getElementById(fld + "_year").value = d.getFullYear();

    // Update the time, if time fields are present.
    if (tm_type != 0) {
        var hour = d.getHours();
        var ampm = 0;
        if (tm_type == "12") {
            if (hour == 0) {
                hour = 12;
            } else if (hour > 12) {
                hour -= 12;
                ampm = 1;
            }     
            document.getElementById(fld + "_ampm").selectedIndex = ampm;
        }
        document.getElementById(fld + "_hour").selectedIndex = hour;
        document.getElementById(fld + "_minute").selectedIndex = d.getMinutes();
    }
}

