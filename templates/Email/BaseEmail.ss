<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
<title>$Subject</title>
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
<style type="text/css">
    html { -webkit-text-size-adjust: none; -ms-text-size-adjust: none;}

	@media only screen and (min-device-width: 750px) {
		.table750 {width: 750px !important;}
	}
	@media only screen and (max-device-width: 750px), only screen and (max-width: 750px){
      table[class="table750"] {width: 100% !important;}
      .top_pad {height: 15px !important; max-height: 15px !important; min-height: 15px !important;}
      .mob_pad {width: 15px !important; max-width: 15px !important; min-width: 15px !important;}
 	}
	.table750 {width: 750px;}
    a {color:$PrimaryColor}
</style>
</head>
<body style="margin: 0; padding: 0;">

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="background: #f3f3f3; min-width: 350px; font-size: 1px; line-height: normal;">
 	<tr>
   	<td align="center" valign="top">
   		<!--[if (gte mso 9)|(IE)]>
         <table border="0" cellspacing="0" cellpadding="0">
         <tr><td align="center" valign="top" width="750"><![endif]-->
   		<table cellpadding="0" cellspacing="0" border="0" width="750" class="table750" style="width: 100%; max-width: 750px; min-width: 350px; background: #f3f3f3;">
   			<tr>
               <td class="mob_pad" width="25" style="width: 25px; max-width: 25px; min-width: 25px;">&nbsp;</td>
   				<td align="center" valign="top" style="background: #ffffff;">

                  <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100% !important; min-width: 100%; max-width: 100%; background: #f3f3f3;">
                     <tr>
                        <td align="right" valign="top">
                           <div class="top_pad" style="height: 25px; line-height: 25px; font-size: 23px;">&nbsp;</div>
                        </td>
                     </tr>
                  </table>

                  <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;">
                     <tr>
                        <td align="center" valign="top">
                           <div style="height: 39px; line-height: 39px; font-size: 37px;">&nbsp;</div>
                           <a href="#" target="_blank" style="display: block; <% if SiteConfig.LogoID %>max-width: 128px;<% else %>text-decoration:none<% end_if %>">
                              <% if SiteConfig.LogoID %>
                                $SiteConfig.Logo.FitMax(128,64)
                              <% else %>
                                <font face="'Source Sans Pro', sans-serif" color="$PrimaryColor" style="font-size: 36px; line-height: 60px; font-weight: 300;">
                                    <span style="font-family: 'Source Sans Pro', Arial, Tahoma, Geneva, sans-serif; color: $PrimaryColor; font-size: 36px; line-height: 60px; font-weight: 300;">$SiteConfig.Title</span>
                                </font>
                              <% end_if %>
                           </a>
                           <div style="height: 39px; line-height: 39px; font-size: 37px;">&nbsp;</div>
                        </td>
                     </tr>
                  </table>

                  <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;">
                     <tr>
                        <td align="left" valign="top">
                            <font face="'Source Sans Pro', sans-serif" color="#868686" style="font-size: 17px; line-height: 20px;">
                            <div style="font-family: 'Source Sans Pro', Arial, Tahoma, Geneva, sans-serif; color: #868686; font-size: 17px; line-height: 20px;">
                            $EmailContent.RAW
                            </div>
                            </font>
                            <div style="height: 39px; line-height: 39px; font-size: 37px;">&nbsp;</div>
                        </td>
                     </tr>
                  </table>

                  <table cellpadding="0" cellspacing="0" border="0" width="100%" style="width: 100% !important; min-width: 100%; max-width: 100%; background: #f3f3f3;">
                     <tr>
                        <td align="center" valign="top">
                            <% if SiteConfig.EmailFooter %>
                            <table cellpadding="0" cellspacing="0" border="0" width="88%" style="width: 88% !important; min-width: 88%; max-width: 88%;">
                                <tr>
                                    <td align="center" valign="top">
                                    <div style="height: 34px; line-height: 34px; font-size: 32px;">&nbsp;</div>
                                    <font face="'Source Sans Pro', sans-serif" color="#868686" style="font-size: 17px; line-height: 20px;">
                                        <span style="font-family: 'Source Sans Pro', Arial, Tahoma, Geneva, sans-serif; color: #868686; font-size: 17px; line-height: 20px;">$SiteConfig.EmailFooter</span>
                                    </font>
                                    <div style="height: 35px; line-height: 35px; font-size: 33px;">&nbsp;</div>
                                    </td>
                                </tr>
                            </table>
                            <% else %>
                            <div style="height: 34px; line-height: 34px; font-size: 32px;">&nbsp;</div>
                            <% end_if %>
                        </td>
                     </tr>
                  </table>

               </td>
               <td class="mob_pad" width="25" style="width: 25px; max-width: 25px; min-width: 25px;">&nbsp;</td>
            </tr>
         </table>
         <!--[if (gte mso 9)|(IE)]>
         </td></tr>
         </table><![endif]-->
      </td>
   </tr>
</table>
</body>
</html>
