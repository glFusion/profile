/*  Updates submission form fields based on changes in the category
 *  dropdown.
 */
var PRFxmlHttp;

function PRFtoggleEnabled(ck, id, type, base_url)
{
  if (ck.checked) {
    newval=1;
  } else {
    newval=0;
  }

  PRFxmlHttp=PRFGetXmlHttpObject();
  if (PRFxmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  var url=base_url + "/admin/plugins/profile/ajax.php?action=toggleEnabled";
  url=url+"&id="+id;
  url=url+"&type="+type;
  url=url+"&newval="+newval;
  url=url+"&sid="+Math.random();
  PRFxmlHttp.onreadystatechange=PRFsc_toggleEnabled;
  PRFxmlHttp.open("GET",url,true);
  PRFxmlHttp.send(null);
}

function PRFsc_toggleEnabled()
{
  var newstate;

  if (PRFxmlHttp.readyState==4 || PRFxmlHttp.readyState=="complete")
  {
    xmlDoc=PRFxmlHttp.responseXML;
    id = xmlDoc.getElementsByTagName("id")[0].childNodes[0].nodeValue;
    //imgurl = xmlDoc.getElementsByTagName("imgurl")[0].childNodes[0].nodeValue;
    baseurl = xmlDoc.getElementsByTagName("baseurl")[0].childNodes[0].nodeValue;
    type = xmlDoc.getElementsByTagName("type")[0].childNodes[0].nodeValue;
    if (xmlDoc.getElementsByTagName("newval")[0].childNodes[0].nodeValue == 1) {
        checked = "checked";
        newval = 0;
    } else {
        checked = "";
        newval = 1;
    }
    document.getElementsByName(type+"_"+id).checked = checked;
    
  }

}

function PRFGetXmlHttpObject()
{
  var objXMLHttp=null
  if (window.XMLHttpRequest)
  {
    objXMLHttp=new XMLHttpRequest()
  }
  else if (window.ActiveXObject)
  {
    objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
  return objXMLHttp
}

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

