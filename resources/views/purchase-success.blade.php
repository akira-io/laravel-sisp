<!-- resources/views/payment.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pagamento vinti4</title>
	<style>
      body, html {
          height: 100%;
          margin: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          font-family: Arial, sans-serif;
      }

      .container {
          text-align: center;
      }

      h5 {
          margin-bottom: 20px;
      }

      .loader {
          border: 8px solid #f3f3f3;
          border-top: 8px solid rgb(239, 68, 68);
          border-radius: 50%;
          width: 32px;
          height: 32px;
          animation: spin 2s linear infinite;
          margin-bottom: 20px;
      }

      @keyframes spin {
          0% {
              transform: rotate(0deg);
          }
          100% {
              transform: rotate(360deg);
          }
      }
	</style>
</head>
<body>
<div class="container">
	<h1>Success Payment</h1>
	@foreach($message as $key => $value)
		<p>{{ $key }}: {{ $value }}</p>
	@endforeach
</div>
</body>
</html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title id="client_data_title">Card Data</title>
	<!-- IMPORT MY HEAD #SISP -->
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/png" href="my_img/logo_vinti4.png">
	<link rel="stylesheet" href="my_lib/bootstrap-5.2.3-dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="my_css/style.css">
	<script src="my_lib/jquery-3.6.4.min.js"></script>
	<script src="my_lib/bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
	<script src="my_lib/cleave.min.js"></script>
	<script src="my_lib/jquery.validate.min.js"></script>
	<script src="my_lib/messages_pt_PT.min.js"></script>
	<script src="my_js/card.js"></script>
	<!--<link rel="stylesheet" type="text/css" href="css/style.css" />-->
	<link rel="stylesheet" type="text/css" href="css/jquery.loading.min.css">
	<script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript" src="js/date.js"></script>
	<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
	<script type="text/javascript" src="js/jquery.validate.min.js"></script>
	<script type="text/javascript" src="js/jquery.loading.min.js"></script>
	<script type="text/javascript" src="js/additional-methods.min.js"></script>
	<script type="text/javascript" src="js/translate.js"></script>
	<script type="text/javascript" src="js/captcha.js"></script>
	<script type="text/javascript" src="js/screen/card/payment.js"></script>
</head>
<body>
<!-- IMPORT MY CARD FORM #SISP -->
<div class="card box">
	<div class="card-body">
		<div class="text-center">
			<img src="my_img/logo_vinti4.jpg" height="40px">
			<img src="my_img/visa-secure-logo.png" height="40px">
			<img src="my_img/mc_idcheck_vrt_rgb_pos.png" height="40px">
			<img src="my_img/amex_logo_full.png">
		</div>
		<br>
		<div class="text-center mt-3">
			<a href="#"
			   class="text-secondary a-hover"
			   data-bs-toggle="collapse"
			   data-bs-target="#detail"
			   style="text-decoration: unset;">
				www.nosferry.cv
			</a>
		</div>
		<div id="detail" class="collapse">
			<div class="alert alert-secondary">
				<p class="text-secondary m-0"><b id="txtType">Type: </b> <span id="valType">Purchase</span></p>
				<p class="text-secondary m-0"><b id="txtStore">Store: </b> www.nosferry.cv</p>
				<p class="text-secondary m-0">
					<b id="txtAmount">Amount: </b>
					100
					CVE
				</p>
				<p class="text-secondary m-0"><b id="txtReference">Reference: </b> R20240726031342</p>
			</div>
		</div>
		<form action="#" method="POST" class="mt-4" autocomplete="on" id="form1" novalidate="novalidate">
			<div class="row">
				<div class="col-12">
					<div class="form-group mb-3">
						<label for="pan" class="text-secondary" id="txtCardNumber">Card Number</label>
						<input type="tel" class="form-control" id="my_pan" name="pan" required="" maxlength="19">
					</div>
				</div>
				<div class="col-8">
					<div class="form-group mb-3">
						<label for="expiration" class="text-secondary" id="txtExpiration">Expiration Date (MM/YY)</label>
						<input type="tel"
						       class="form-control"
						       id="my_expiration"
						       name="expiration"
						       required=""
						       minlength="5"
						       maxlength="5">
					</div>
				</div>
				<div class="col-4">
					<div class="form-group mb-3">
						<label for="cvv2" class="text-secondary">
							CVV2
							<i data-bs-toggle="modal" data-bs-target="#cvv2Modal">
								<img src="my_img/info.svg" height="16px">
							</i>
						</label>
						<input type="number" class="form-control" id="my_cvv2" name="cvv2" required="" minlength="3" maxlength="4">
					</div>
				</div>
			</div>
			<div id="btn-form1-submit">
				<div class="d-grid gap-2">
					<button class="btn btn-primary btn-block">
						<span id="txtConfirm">Confirm</span>
						100
						CVE
					</button>
				</div>
			</div>
			<div id="form1-loader" class="text-center">
				<div class="spinner-border text-primary"></div>
			</div>
		</form>
		<div class="text-center mt-2">
			<a href="#" class="text-secondary a-hover" id="txtCancell" style="text-decoration: unset;">Cancel</a>
		</div>
	</div>
</div>
<div class="modal" id="cvv2Modal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="txtWhatIsCVV">What is CVV2?</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="text-center">
					<img src="my_img/cvv2.svg" height="128px">
				</div>
				<br>
				<div id="txtWhatIsCVVText">
					<p>The CVV2 (Card Verification Value) number is a 3-digit number on the credit and debit cards of the brands vinti4, Visa and MasterCard.</p>
					<p>CVV2 numbers are not your card's secret PIN (personal identification number).</p>
					<p>CVV2 = CSC</p></div>
			</div>
		</div>
	</div>
</div>
<input id="message_Error_Capcha" name="message_Error_Capcha" type="hidden" value="Please, verify image again.">
<form class="d-none"
      id="formPaymentData"
      name="formPaymentData"
      enctype="application/x-www-form-urlencoded"
      style=""
      novalidate="novalidate">
	<div id="mainDiv" class="mainDiv">
		<div class="imageTopLeft">
		</div>
		<div class="imageTopRight">
			<img style="height: 40px;" alt="Purchase Acquirer" src="images/LOGOTIPO1.png">
			<img style="height: 40px;" alt="Visa Secure" src="images/visa-secure-logo.png">
			<img style="height: 50px;" alt="MasterCard Id Check" src="images/mc-idcheck-logo.png">
			<img style="height: 40px;" alt="AMEX" src="images/amex_logo_full.png">
		</div>
		<div id="info_pay" class="headerDiv">Payment Information</div>
		<div class="contentDiv">
			<div id="info_pay_confirm">Please confirm your payment information bellow:</div>
			<table align="center">
				<tbody>
				<tr>
					<td id="serv_type_key" class="property">Service Type</td>
					<td>&nbsp;</td>
					<td id="serv_type_value_1" class="value">Purchase</td>
				</tr>
				<tr>
					<td id="store_name" class="property">Store Name</td>
					<td>&nbsp;</td>
					<td id="merchantHost" class="value">
						www.nosferry.cv
					</td>
				</tr>
				<tr>
					<td class="property">
						<div id="value">Value</div>
					</td>
					<td>&nbsp;</td>
					<td class="value">
						100
					</td>
				</tr>
				<tr>
					<td id="merchant_reference" class="property">Merchant Reference</td>
					<td>&nbsp;</td>
					<td class="value">
						R20240726031342
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<div id="info_card" class="headerDiv">Card Information</div>
		<div class="contentDiv">
			<div id="info_card_allowed" style="float: left;">Cards Allowed:</div>
			<div style="float: left;">
				&nbsp;&nbsp;
				<img src="images/CARD_OWN_LOGO.png" alt="card" style="height: 25px;">
				<img src="images/mastercard.png" alt="MasterCard" style="height: 25px;">
				<img src="images/visa.gif" alt="Visa" style="height: 25px;">
				<img src="images/amex_logo.png" alt="AMEX" style="height: 25px;">
			</div>
			<div id="info_card_confirm" style="clear: both; margin-top: 10px;">Please, fill card data bellow:</div>
			<div>
				<table align="center">
					<tbody>
					<tr>
						<td class="property">PAN</td>
						<td>&nbsp;</td>
						<td class="value">
							<input type="text" id="pan" name="pan" maxlength="21" style="width: 150px;" value="" autocomplete="off">
						</td>
					</tr>
					<tr>
						<td id="expiry_date" class="property">Expiry Date (MMyyyy)</td>
						<td>&nbsp;</td>
						<td class="value">
							<input type="text"
							       id="dateMonthYear"
							       name="dateMonthYear"
							       maxlength="6"
							       style="width: 50px;"
							       value=""
							       autocomplete="off">
						</td>
					</tr>
					<tr>
						<td id="CVV2_label" class="property">CVV2</td>
						<td>&nbsp;</td>
						<td class="value">
							<input type="text" id="cvv2" name="cvv2" maxlength="3" style="width: 50px;" value="" autocomplete="off">
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div id="info_security_check" class="headerDiv">Security Check</div>
			<div class="contentDiv">
				<div id="info_security_check_confirm">Please, write the characters on the image bellow:</div>
				<table align="center">
					<tbody>
					<tr>
						<td class="property">
							<img id="captchaImage"
							     src="servlet/captcha-image.jpg?timestamp=1721963623502&amp;sessionId=355408870637596405143091176125233730087042415930763199511016282999439739426019843820812574142932583">
							<input type="hidden"
							       id="captcha_session"
							       name="captcha_session"
							       value="355408870637596405143091176125233730087042415930763199511016282999439739426019843820812574142932583">
						</td>
						<td>&nbsp;</td>
						<td class="value">
							<img id="refreshCaptcha" src="images/refresh.ico" style="width:20px; height: 20px;" autocomplete="off">
							<input type="text" id="captcha" name="captcha" maxlength="6" style="width:100px;" value="">
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div class="centeredButtonsDiv">
				<input id="button_Cancel" type="button" class="button" value="Exit">
				&nbsp;
				&nbsp;
				<input id="button_Pay" type="button" class="button" value="Submit">
			</div>
		</div>
	</div>
	<!-- Hidden Fields -->
	<input id="messageID" type="hidden" name="messageID" value="lqlCRP84f55t7o2d9OJo">
	<input id="amount" type="hidden" name="amount" value="100">
	<input id="merchantRef" type="hidden" name="merchantRef" value="R20240726031342">
	<input id="merchantSession" type="hidden" name="merchantSession" value="S20240726031342">
	<input id="posID" type="hidden" name="posID" value="90000155">
	<input id="currency" type="hidden" name="currency" value="132">
	<input id="urlMerchantResponse"
	       type="hidden"
	       name="urlMerchantResponse"
	       value="http://plugin.test/sisp-payment-response">
	<input id="is3DSec" type="hidden" name="is3DSec" value="false">
	<input id="TimeStamp" type="hidden" name="TimeStamp" value="2024-07-26 03:13:42">
	<input id="FingerPrint"
	       type="hidden"
	       name="FingerPrint"
	       value="4oLt9LBFqYNFm5ZZAim1Z9eok8J5K+XgVCKkP/KXajzr3agpAsq6gNiZ3UPUSnc81QV1tW0uIce4/IoDkUvTIg==">
	<input id="FingerPrintVersion" type="hidden" name="FingerPrintVersion" value="1">
	<input id="transactionCode" type="hidden" name="transactionCode" value="1">
	<input id="languageMessages" type="hidden" name="languageMessages" value="en">
	<input id="languageMessagesDefault" type="hidden" name="languageMessagesDefault" value="en">
	<input id="urlFrontend" type="hidden" name="urlFrontend" value="https://vinti4visanet:8443/BizMPIServerOnUs/">
	<input id="requestDomainAddress" type="hidden" name="requestDomainAddress" value="www.nosferry.cv">
	<input id="referenceNumber" type="hidden" name="referenceNumber" value="">
	<input id="entityCode" type="hidden" name="entityCode" value="">
	<input id="urlEMV3DSPurchaseBuilder" type="hidden" name="urlEMV3DSPurchaseBuilder" value="">
	<input id="e3dsSessionData" type="hidden" name="e3dsSessionData" value="">
</form>
<form id="formCancel"
      name="formCancel"
      method="post"
      enctype="application/x-www-form-urlencoded"
      action="http://plugin.test/sisp-payment-response">
	<input type="hidden" name="merchantRef" value="R20240726031342">
	<input type="hidden" name="merchantSession" value="S20240726031342">
	<input type="hidden" name="UserCancelled" value="true">
</form>
<div class="loading-overlay loading-theme-light loading-hidden"
     style="position: absolute; z-index: 1; top: 0px; left: 0px; width: 0px; height: 0px; display: none;"
     id="formPaymentData_loading-overlay">
	<div class="loading-overlay-content">Loading...</div>
</div>
</body>
</html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title id="client_data_title">Card Data</title>
	<!-- IMPORT MY HEAD #SISP -->
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="icon" type="image/png" href="my_img/logo_vinti4.png">
	<link rel="stylesheet" href="my_lib/bootstrap-5.2.3-dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="my_css/style.css">
	<script src="my_lib/jquery-3.6.4.min.js"></script>
	<script src="my_lib/bootstrap-5.2.3-dist/js/bootstrap.min.js"></script>
	<script src="my_lib/cleave.min.js"></script>
	<script src="my_lib/jquery.validate.min.js"></script>
	<script src="my_lib/messages_pt_PT.min.js"></script>
	<script src="my_js/card.js"></script>
	<!--<link rel="stylesheet" type="text/css" href="css/style.css" />-->
	<link rel="stylesheet" type="text/css" href="css/jquery.loading.min.css">
	<script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript" src="js/date.js"></script>
	<script type="text/javascript" src="js/jquery-3.6.0.min.js"></script>
	<script type="text/javascript" src="js/jquery.validate.min.js"></script>
	<script type="text/javascript" src="js/jquery.loading.min.js"></script>
	<script type="text/javascript" src="js/additional-methods.min.js"></script>
	<script type="text/javascript" src="js/translate.js"></script>
	<script type="text/javascript" src="js/captcha.js"></script>
	<script type="text/javascript" src="js/screen/card/payment.js"></script>
</head>
<body>
<!-- IMPORT MY CARD FORM #SISP -->
<div class="card box">
	<div class="card-body">
		<div class="text-center">
			<img src="my_img/logo_vinti4.jpg" height="40px">
			<img src="my_img/visa-secure-logo.png" height="40px">
			<img src="my_img/mc_idcheck_vrt_rgb_pos.png" height="40px">
			<img src="my_img/amex_logo_full.png">
		</div>
		<br>
		<div class="text-center mt-3">
			<a href="#"
			   class="text-secondary a-hover"
			   data-bs-toggle="collapse"
			   data-bs-target="#detail"
			   style="text-decoration: unset;">
				www.nosferry.cv
			</a>
		</div>
		<div id="detail" class="collapse">
			<div class="alert alert-secondary">
				<p class="text-secondary m-0"><b id="txtType">Type: </b> <span id="valType">Purchase</span></p>
				<p class="text-secondary m-0"><b id="txtStore">Store: </b> www.nosferry.cv</p>
				<p class="text-secondary m-0">
					<b id="txtAmount">Amount: </b>
					100
					CVE
				</p>
				<p class="text-secondary m-0"><b id="txtReference">Reference: </b> R20240726031342</p>
			</div>
		</div>
		<form action="#" method="POST" class="mt-4" autocomplete="on" id="form1" novalidate="novalidate">
			<div class="row">
				<div class="col-12">
					<div class="form-group mb-3">
						<label for="pan" class="text-secondary" id="txtCardNumber">Card Number</label>
						<input type="tel" class="form-control" id="my_pan" name="pan" required="" maxlength="19">
					</div>
				</div>
				<div class="col-8">
					<div class="form-group mb-3">
						<label for="expiration" class="text-secondary" id="txtExpiration">Expiration Date (MM/YY)</label>
						<input type="tel"
						       class="form-control"
						       id="my_expiration"
						       name="expiration"
						       required=""
						       minlength="5"
						       maxlength="5">
					</div>
				</div>
				<div class="col-4">
					<div class="form-group mb-3">
						<label for="cvv2" class="text-secondary">
							CVV2
							<i data-bs-toggle="modal" data-bs-target="#cvv2Modal">
								<img src="my_img/info.svg" height="16px">
							</i>
						</label>
						<input type="number" class="form-control" id="my_cvv2" name="cvv2" required="" minlength="3" maxlength="4">
					</div>
				</div>
			</div>
			<div id="btn-form1-submit">
				<div class="d-grid gap-2">
					<button class="btn btn-primary btn-block">
						<span id="txtConfirm">Confirm</span>
						100
						CVE
					</button>
				</div>
			</div>
			<div id="form1-loader" class="text-center">
				<div class="spinner-border text-primary"></div>
			</div>
		</form>
		<div class="text-center mt-2">
			<a href="#" class="text-secondary a-hover" id="txtCancell" style="text-decoration: unset;">Cancel</a>
		</div>
	</div>
</div>
<div class="modal" id="cvv2Modal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="txtWhatIsCVV">What is CVV2?</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="text-center">
					<img src="my_img/cvv2.svg" height="128px">
				</div>
				<br>
				<div id="txtWhatIsCVVText">
					<p>The CVV2 (Card Verification Value) number is a 3-digit number on the credit and debit cards of the brands vinti4, Visa and MasterCard.</p>
					<p>CVV2 numbers are not your card's secret PIN (personal identification number).</p>
					<p>CVV2 = CSC</p></div>
			</div>
		</div>
	</div>
</div>
<input id="message_Error_Capcha" name="message_Error_Capcha" type="hidden" value="Please, verify image again.">
<form class="d-none"
      id="formPaymentData"
      name="formPaymentData"
      enctype="application/x-www-form-urlencoded"
      style=""
      novalidate="novalidate">
	<div id="mainDiv" class="mainDiv">
		<div class="imageTopLeft">
		</div>
		<div class="imageTopRight">
			<img style="height: 40px;" alt="Purchase Acquirer" src="images/LOGOTIPO1.png">
			<img style="height: 40px;" alt="Visa Secure" src="images/visa-secure-logo.png">
			<img style="height: 50px;" alt="MasterCard Id Check" src="images/mc-idcheck-logo.png">
			<img style="height: 40px;" alt="AMEX" src="images/amex_logo_full.png">
		</div>
		<div id="info_pay" class="headerDiv">Payment Information</div>
		<div class="contentDiv">
			<div id="info_pay_confirm">Please confirm your payment information bellow:</div>
			<table align="center">
				<tbody>
				<tr>
					<td id="serv_type_key" class="property">Service Type</td>
					<td>&nbsp;</td>
					<td id="serv_type_value_1" class="value">Purchase</td>
				</tr>
				<tr>
					<td id="store_name" class="property">Store Name</td>
					<td>&nbsp;</td>
					<td id="merchantHost" class="value">
						www.nosferry.cv
					</td>
				</tr>
				<tr>
					<td class="property">
						<div id="value">Value</div>
					</td>
					<td>&nbsp;</td>
					<td class="value">
						100
					</td>
				</tr>
				<tr>
					<td id="merchant_reference" class="property">Merchant Reference</td>
					<td>&nbsp;</td>
					<td class="value">
						R20240726031342
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<div id="info_card" class="headerDiv">Card Information</div>
		<div class="contentDiv">
			<div id="info_card_allowed" style="float: left;">Cards Allowed:</div>
			<div style="float: left;">
				&nbsp;&nbsp;
				<img src="images/CARD_OWN_LOGO.png" alt="card" style="height: 25px;">
				<img src="images/mastercard.png" alt="MasterCard" style="height: 25px;">
				<img src="images/visa.gif" alt="Visa" style="height: 25px;">
				<img src="images/amex_logo.png" alt="AMEX" style="height: 25px;">
			</div>
			<div id="info_card_confirm" style="clear: both; margin-top: 10px;">Please, fill card data bellow:</div>
			<div>
				<table align="center">
					<tbody>
					<tr>
						<td class="property">PAN</td>
						<td>&nbsp;</td>
						<td class="value">
							<input type="text" id="pan" name="pan" maxlength="21" style="width: 150px;" value="" autocomplete="off">
						</td>
					</tr>
					<tr>
						<td id="expiry_date" class="property">Expiry Date (MMyyyy)</td>
						<td>&nbsp;</td>
						<td class="value">
							<input type="text"
							       id="dateMonthYear"
							       name="dateMonthYear"
							       maxlength="6"
							       style="width: 50px;"
							       value=""
							       autocomplete="off">
						</td>
					</tr>
					<tr>
						<td id="CVV2_label" class="property">CVV2</td>
						<td>&nbsp;</td>
						<td class="value">
							<input type="text" id="cvv2" name="cvv2" maxlength="3" style="width: 50px;" value="" autocomplete="off">
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div id="info_security_check" class="headerDiv">Security Check</div>
			<div class="contentDiv">
				<div id="info_security_check_confirm">Please, write the characters on the image bellow:</div>
				<table align="center">
					<tbody>
					<tr>
						<td class="property">
							<img id="captchaImage"
							     src="servlet/captcha-image.jpg?timestamp=1721963623502&amp;sessionId=355408870637596405143091176125233730087042415930763199511016282999439739426019843820812574142932583">
							<input type="hidden"
							       id="captcha_session"
							       name="captcha_session"
							       value="355408870637596405143091176125233730087042415930763199511016282999439739426019843820812574142932583">
						</td>
						<td>&nbsp;</td>
						<td class="value">
							<img id="refreshCaptcha" src="images/refresh.ico" style="width:20px; height: 20px;" autocomplete="off">
							<input type="text" id="captcha" name="captcha" maxlength="6" style="width:100px;" value="">
						</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div class="centeredButtonsDiv">
				<input id="button_Cancel" type="button" class="button" value="Exit">
				&nbsp;
				&nbsp;
				<input id="button_Pay" type="button" class="button" value="Submit">
			</div>
		</div>
	</div>
	<!-- Hidden Fields -->
	<input id="messageID" type="hidden" name="messageID" value="lqlCRP84f55t7o2d9OJo">
	<input id="amount" type="hidden" name="amount" value="100">
	<input id="merchantRef" type="hidden" name="merchantRef" value="R20240726031342">
	<input id="merchantSession" type="hidden" name="merchantSession" value="S20240726031342">
	<input id="posID" type="hidden" name="posID" value="90000155">
	<input id="currency" type="hidden" name="currency" value="132">
	<input id="urlMerchantResponse"
	       type="hidden"
	       name="urlMerchantResponse"
	       value="http://plugin.test/sisp-payment-response">
	<input id="is3DSec" type="hidden" name="is3DSec" value="false">
	<input id="TimeStamp" type="hidden" name="TimeStamp" value="2024-07-26 03:13:42">
	<input id="FingerPrint"
	       type="hidden"
	       name="FingerPrint"
	       value="4oLt9LBFqYNFm5ZZAim1Z9eok8J5K+XgVCKkP/KXajzr3agpAsq6gNiZ3UPUSnc81QV1tW0uIce4/IoDkUvTIg==">
	<input id="FingerPrintVersion" type="hidden" name="FingerPrintVersion" value="1">
	<input id="transactionCode" type="hidden" name="transactionCode" value="1">
	<input id="languageMessages" type="hidden" name="languageMessages" value="en">
	<input id="languageMessagesDefault" type="hidden" name="languageMessagesDefault" value="en">
	<input id="urlFrontend" type="hidden" name="urlFrontend" value="https://vinti4visanet:8443/BizMPIServerOnUs/">
	<input id="requestDomainAddress" type="hidden" name="requestDomainAddress" value="www.nosferry.cv">
	<input id="referenceNumber" type="hidden" name="referenceNumber" value="">
	<input id="entityCode" type="hidden" name="entityCode" value="">
	<input id="urlEMV3DSPurchaseBuilder" type="hidden" name="urlEMV3DSPurchaseBuilder" value="">
	<input id="e3dsSessionData" type="hidden" name="e3dsSessionData" value="">
</form>
<form id="formCancel"
      name="formCancel"
      method="post"
      enctype="application/x-www-form-urlencoded"
      action="http://plugin.test/sisp-payment-response">
	<input type="hidden" name="merchantRef" value="R20240726031342">
	<input type="hidden" name="merchantSession" value="S20240726031342">
	<input type="hidden" name="UserCancelled" value="true">
</form>
<div class="loading-overlay loading-theme-light loading-hidden"
     style="position: absolute; z-index: 1; top: 0px; left: 0px; width: 0px; height: 0px; display: none;"
     id="formPaymentData_loading-overlay">
	<div class="loading-overlay-content">Loading...</div>
</div>
</body>
</html>
