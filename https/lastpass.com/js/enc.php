<html>
<!-- Mirrored from lastpass.com/js/enc.php by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 14 Jun 2017 21:06:44 GMT -->
<!-- Added by HTTrack --><meta http-equiv="content-type" content="text/html;charset=UTF-8" /><!-- /Added by HTTrack -->
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Simple LastPass Encryption/Decryption page</title><script type="text/javascript" nonce="V4V4o26obO8RAEpNsBeGbKa_pE9wD4ZlL2UHrX1QdOo=">
LPNAME='LastPass';
logouttext='';
firefoxwebapp=false;
bookmarklet=false;
  </script><script type="text/javascript" src="https://lastpass.com/m.php/aessha?1497460954"></script><script type="text/javascript" src="https://lastpass.com/m.php/rsa?1497460953"></script><script type="text/javascript" src="https://lastpass.com/m.php/jquery?1405569638"></script><script type="text/javascript" src="https://lastpass.com/m.php/jqueryts?1405569638"></script><script type="text/javascript" src="https://lastpass.com/m.php/rngmobile?1497460953"></script><script type="text/javascript" src="https://lastpass.com/m.php/mobilejs?1497460954"></script><script type="text/javascript" src="https://lastpass.com/m.php/xss?1497460953"></script><script type="text/javascript" src="https://lastpass.com/m.php/rsa_privkey?1497460954"></script></head><body onload="onLoad()"><br><script type="text/javascript" nonce="V4V4o26obO8RAEpNsBeGbKa_pE9wD4ZlL2UHrX1QdOo=">
function onLoad()
{
  document.getElementById("username").disabled = false;
  document.getElementById("password").disabled = false;
  document.getElementById("iterations").disabled = false;
  //document.getElementById("username").value = "";
  document.getElementById("password").value = "";
  //document.getElementById("iterations").value = "5000";

  document.getElementById("key").value = "";
  document.getElementById("lhash").value = "";

  document.getElementById("datae").value = "abcdefABCDEF0123456789";
  document.getElementById("datad").value = "";
  
}

function fix_username(u)
{
  return u.toLowerCase().replace(/\s*/g, "");
}

function ishexstring(s)
{
  for (i=0 ; i<s.length ; ++i)
  {
    var c = s.charAt(i).toLowerCase();
    if ((c<"a" || c>"f") && (c<"0" || c>"9"))
    {
      alert("The entered value is not a valid hexidecimal string");
      return false;
    }
  }
  return true;
}

// Ugly but easier to understand this way
function globals()
{
  username=document.getElementById("username");
  password=document.getElementById("password");
  iterations=document.getElementById("iterations");
  datae=document.getElementById("datae");
  datad=document.getElementById("datad");

  if(!is_int(iterations.value)){
    alert(iterations.value + " is not an integer. Defaulting to 1");
    iterations.value = 1;
  }

  if(iterations.value==1){
    mykey=AES.hex2bin(SHA256(fix_username(username.value)+password.value));

    // The hash used as a key, turned back into hex
    document.getElementById("key").value = AES.bin2hex(mykey);

    // The hash used to login
    document.getElementById("lhash").value = SHA256(SHA256(fix_username(username.value)+password.value)+password.value);
  }else{
    mykey = make_lp_key_iterations(username.value,password.value,iterations.value);
    document.getElementById("key").value = AES.bin2hex(mykey);
    document.getElementById("lhash").value = make_lp_hash_iterations(mykey,password.value,iterations.value);
  }
  g_local_key=mykey;
}

function is_int(value){ 
  if((parseFloat(value) == parseInt(value)) && !isNaN(value)){
      return true;
  } else { 
      return false;
  } 
}

function fake_login()
{
  var username = document.getElementById("username").value;
  var password = document.getElementById("password").value;
  if (username=="" || password=="")
  {
    alert("Please enter your credentials");
    return;
  }
  doenc();
  var key   = document.getElementById("key").value;
  var lhash = document.getElementById("lhash").value;
  if (key=="" || lhash=="")
  {
    alert("ERROR: Encryption failed!");
    return;
  }
  
  document.getElementById("username").disabled = true;
  document.getElementById("password").disabled = true;
  document.getElementById("iterations").disabled = true;

  document.getElementById("rowkey").style.display   = "";
  document.getElementById("rowlhash").style.display = "";
  document.getElementById("rowenc").style.display   = "";
  document.getElementById("rowencb64").style.display   = "";
  document.getElementById("rowdec").style.display   = "";

  
}

function dodec()
{
  globals();
  if(datad.value.length) {
    var mydec = "";
    try{
      if(datad.value[0]=="!" && datad.value.indexOf("|") > 0){
        //CBC
        var aeski = AES.StringToKeyIv(mykey,256);
        var aKey  = [];
        for (i=0 ; i<aeski.key.length ; ++i)
          aKey[i] = aeski.key[i];

        var ivbytes = [];
        var data;
        var s = datad.value;
        var idx = s.indexOf("|");
        if (idx != -1) {
          var iv = atob(s.substring(1, idx));
          for (var i=0 ; i<16 ; ++i)
            ivbytes[i] = iv.charCodeAt(i);
          data = s.substring(idx + 1);
        }
        mydec = AES.Decrypt({key:aKey, iv:ivbytes, data:data, b64:true, bits:256, mode:"cbc"});

      }else{
        //ECB
        mydec = (AES.Decrypt({pass:mykey, data:datad.value, b64:true, bits:256}));
      }
    } catch (e) {
      alert("ERROR: Decryption failed");
    }
    datae.value = mydec;
    datadb64.value = btoa(mydec);
  } else {
    datae.value    = "";
    datadb64.value = "";
  }
}

function doenc()
{
  globals();
  if(datae.value.length) {
    if(!document.getElementById("CBC").checked){
      var myenc=(AES.Encrypt({pass:mykey, data:datae.value, b64:true, bits:256}));
      datad.value = myenc;
    }else{
      var iv = "";
      var aIV = [];
      for (k=0 ; k<16 ; ++k)
      {
        //aIV[k] = Math.floor(Math.random()*256);
        aIV[k] = get_random(0, 255);
        iv += String.fromCharCode(aIV[k]);
      }

      var aeski = AES.StringToKeyIv(mykey,256);
      var aKey  = [];
      for (i=0 ; i<aeski.key.length ; ++i)
        aKey[i] = aeski.key[i];

      var myenc = "!" + AES.eb64(iv) + "|" + AES.Encrypt({key:aKey, iv:aIV, data:datae.value, b64:true, bits:256, mode:"cbc"});
      datad.value = myenc;
    }
  } else 
    datad.value = "";
}</script> LastPass has created this page to help verify the encryption methods used by LastPass. <br> To use this tool you must enter your LastPass Username and LastPass Master Password. You can then encrypt or decrypt any data that was encrypted by LastPass. All encrypted data is base64'ed.<br><br>This page has no network connectivity. It is 100% local JavaScript. You can utilize TamperData in Firefox to collect your data to test against these results. This same method is applied to allow login via a web page without ever sending your master password / key to LastPass.<br><br><form action="https://127.0.0.1/" method="post"><table><tr><td>LastPass Email</td><td><input type="text" name="username" id="username" value=""></td></tr><tr><td>LastPass Password</td><td><input type="password" id="password"></td></tr><tr><td><a href="http://helpdesk.lastpass.com/security-options/password-iterations-pbkdf2/" target="_blank">Number of PBKDF2 Iterations</a></td><td><input type="text" id="iterations" value="5000"></td></tr><tr><td></td><td><input type="button" value="GENERATE" onclick="fake_login();"></td></tr><tr><td colspan="2"><br><br><br></td></tr><tr id="rowkey" style="display:none;"><td>Encryption key hash</td><td><input type="text" id="key" size="80" disabled></td></tr><tr id="rowlhash" style="display:none;"><td>Login hash</td><td><input type="text" id="lhash" size="80" disabled></td></tr><tr><td colspan="2"><br><br><br></td></tr><tr id="rowenc" style="display:none;"><td>Data to AES Encrypt</td><td><table><tr><td><textarea id="datae" cols="80" rows="5"></textarea></td><td><input type="checkbox" id="CBC" checked>CBC<br><input type="button" value="Encrypt" onclick="doenc();"></td></tr></table></td></tr><tr id="rowencb64" style="display:none;"><td>AES Decrypted data b64</td><td><input type="text" id="datadb64" size="80"></td></tr><tr id="rowdec" style="display:none;"><td>Data to AES Decrypt</td><td><textarea id="datad" cols="80" rows="5"></textarea><input type="button" value="Decrypt" onclick="dodec();"></td></tr></table></form><div id="statusdiv"></div><div id="main"></div><div id="main2"></div></body>
<!-- Mirrored from lastpass.com/js/enc.php by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 14 Jun 2017 21:06:45 GMT -->
</html>
