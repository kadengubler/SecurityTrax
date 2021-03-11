<!DOCTYPE HTML>
<html>
<head>
<style>
.error {color: #FF0000;}
</style>
</head>
<body>

<?php
// define variables and set values
$profile = "sandbox"; //dealer profile name or sandbox for staging
$environment = "stage"; // stage or prod 
$integratorKey = ""; //use the integrator key provided by SecurityTrax
$username = ""; //use the username provided by SecurityTrax
$password = ""; //use the password provided by SecurityTrax
$lead_company_id = "1"; //id of lead company from environment
$sale_date = "0000-00-00"; //sale_date for lead must be 0000-00-00, actual sale_date required for customer
$record_type = "lead"; //lead or customer
$account_type = "Residential"; //Residential or Business. If business then business_name and business_contact must be included

// define form variables and set to empty values
$fnameErr = $lnameErr = $emailErr = $phoneErr = $address1Err = $address2Err = $cityErr = $stateErr = $zipErr = $ownershipErr = $contactTimeErr = "";
$fname = $lname = $email = $primary_phone = $address1 = $address2 = $city = $state = $zip = $ownership = $contact_time = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["fname"])) {
        $fnameErr = "First Name is required";
    } else {
        $fname = test_input($_POST["fname"]);
        // check if name only contains letters
        if (!preg_match("/^[a-zA-Z]*$/", $fname)) {
            $fnameErr = "Only letters allowed";
        }
    }

    if (empty($_POST["lname"])) {
        $lnameErr = "Last Name is required";
    } else {
        $lname = test_input($_POST["lname"]);
        // check if name only contains letters
        if (!preg_match("/^[a-zA-Z]*$/", $lname)) {
            $lnameErr = "Only letters allowed";
        }
    }

    if (empty($_POST["email"])) {
        $emailErr = "";
    } else {
        $email = test_input($_POST["email"]);
        // check if e-mail address is well-formed
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    if (empty($_POST["primary_phone"])) {
        $primaryPhoneErr = "Phone number is required";
    } else {
        $primary_phone = test_input($_POST["primary_phone"]);

        //eliminate every char except 0-9
        $cleanPhone = preg_replace("/[^0-9]/", '', $primary_phone);
        //eliminate leading 1 if its there
        if (strlen($cleanPhone) == 11) {
            $cleanPhone = preg_replace("/^1/", '', $cleanPhone);
        }
        //if we have 10 digits left, it's probably valid.
        if (strlen($cleanPhone) == 10) {
            $primary_phone = $cleanPhone;
        } else {
            $primaryPhoneErr = "Phone number does not appear valid";
        }
    }

    if (empty($_POST["address1"])) {
        $address1Err = "Address is required";
    } else {
        $address1 = test_input($_POST["address1"]);
    }

    if (empty($_POST["address2"])) {
    } else {
        $address2 = test_input($_POST["address2"]);
    }

    if (empty($_POST["city"])) {
        $cityErr = "City is required";
    } else {
        $city = test_input($_POST["city"]);
        // check if city only contains letters
        if (!preg_match("/^[a-zA-Z]*$/", $city)) {
            $cityErr = "Only letters allowed";
        }
    }

    if (empty($_POST["state"])) {
        $stateErr = "State is required";
    } else {
        $state = test_input($_POST["state"]);
    }

    if (empty($_POST["zip"])) {
        $zipErr = "Zip code is required";
    } else {
        $zip = test_input($_POST["zip"]);
        // check if zip is only numbers
        if (!preg_match("/^[0-9]*$/", $zip)) {
            $zipErr = "Only numbers are allowed";
        }
        // check if zip is 5 digits
        if (!strlen($zip) == 5) {
            $zipErr = "Use 5 digit zip code";
        }
    }

    if (empty($_POST["ownership"])) {
        $ownershipErr = "Home ownership is required";
    } else {
        $ownership = test_input($_POST["ownership"]);
    }

    if (empty($_POST["contact_time"])) {
    } else {
        $str = implode(", ", $_POST["contact_time"]);
        $contact_time = test_input($str);
    }

    // Post to SecurityTrax if no validation errors
    if (empty($fnameErr) & empty($lnameErr) & empty($primaryPhoneErr) & empty($emailErr) & empty($address1Err) & empty($address2Err) & empty($cityErr) & empty($stateErr) & empty($zipErr) & empty($ownershipErr) & empty($contactTimeErr)) {
        // build the body
        $body = json_decode('{"data":{"type":"customers","attributes":{"fname":"{{fname}}","lname":"{{lname}}","primary_phone":"{{phone1}}","address1":"{{address1}}","address2":"{{address2}}","city":"{{city}}","state":"{{state}}","zip":"{{zip}}","sale_date":"0000-00-00","email":"{{email}}","account_type":"{{account_type}}","record_type":"lead","home_ownership":"{{ome_ownership}}","contact_time":"{{contact_time}}"},"relationships":{"lead_company":{"data":{"id":"{{lead_company_id}}","type":"lead_companies"}}}}}', true);

        // extract($body);

        $body['data']['attributes']['lname'] = $lname;
        $body['data']['attributes']['fname'] = $fname;
        $body['data']['attributes']['primary_phone'] = $primary_phone;
        $body['data']['attributes']['address1'] = $address1;
        $body['data']['attributes']['address2'] = $address2;
        $body['data']['attributes']['city'] = $city;
        $body['data']['attributes']['state'] = $state;
        $body['data']['attributes']['zip'] = $zip;
        $body['data']['attributes']['sale_date'] = $sale_date;
        $body['data']['attributes']['email'] = $email;
        $body['data']['attributes']['record_type'] = $record_type;
        $body['data']['attributes']['home_ownership'] = $ownership;
        $body['data']['attributes']['contact_time'] = $contact_time;
        $body['data']['attributes']['account_type'] = $account_type;
        $body['data']['relationships']['lead_company']['data']['id'] = $lead_company_id;

        $body = json_encode($body);

        $header = array(
            "Content-Type: application/vnd.api+json",
            "X-SecurityTrax-IntegratorKey: " . $integratorKey,
        );

        // Get token and add to header
        array_push($header, "Authorization: Bearer " . auth());
        switch ($environment) {
            case "stage":
                $url = "https://api.staging.securitytrax.com/" . $profile . "/user/v1/customers";
            break;
            case "prod":
                $url = "https://api.securitytrax.com/" . $profile . "/user/v1/customers";
            break;
        }
        

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $header,
        ));

        $response = curl_exec($curl);

        $responseCode =
            curl_getinfo($curl,
            CURLINFO_HTTP_CODE
        );
        curl_close($curl);
    }
}

function auth()
{
    global $profile, $username, $password, $header, $environment;
    switch ($environment) {
        case "stage":
            $url = "https://api.staging.securitytrax.com/" . $profile . "/user/v1/authenticate";
        break;
        case "prod":
            $url = "https://api.securitytrax.com/" . $profile . "/user/v1/authenticate";
        break;
    }

    $curl = curl_init();
    $body = "{\n\t\"username\": \"" . $username . "\",\n\t\"password\": \"" . $password . "\"\n}";

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => $header,
    ));

    $response = json_decode(curl_exec($curl))->{'token'};

    curl_close($curl);

    return $response;
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function IsChecked($chkname,$value)
    {
        if(!empty($_POST[$chkname]))
        {
            foreach($_POST[$chkname] as $chkval)
            {
                if($chkval == $value)
                {
                    return true;
                }
            }
        }
        return false;
    }

?>

<h2>SecurityTrax Leads Form Example</h2>
<p><span class="error">* required field</span></p>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
  First Name: <input type="text" name="fname" value="<?php echo $fname; ?>">
  <span class="error">* <?php echo $fnameErr; ?></span>
  <br><br>
  Last Name: <input type="text" name="lname" value="<?php echo $lname; ?>">
  <span class="error">* <?php echo $lnameErr; ?></span>
  <br><br>
  Phone Number: <input type="text" name="primary_phone" value="<?php echo $primary_phone; ?>">
  <span class="error">* <?php echo $primaryPhoneErr; ?></span>
  <br><br>
  Address: <input type="text" name="address1" value="<?php echo $address1; ?>">
  <span class="error">* <?php echo $Address1Err; ?></span>
  <br><br>
  Address2: <input type="text" name="address2" value="<?php echo $address2; ?>">
  <span class="error"><?php echo $Address2Err; ?></span>
  <br><br>
  City: <input type="text" name="city" value="<?php echo $city; ?>">
  <span class="error">* <?php echo $cityErr; ?></span>
  <br><br>
  State: <select id="state" name="state" selected="<?php echo $state; ?>">
    <option value="" selected disabled hidden></option>
    <option <?php if ($_POST['state'] == 'AL') {?>selected="true" <?php };?> value="AL">AL</option>
	<option <?php if ($_POST['state'] == 'AK') {?>selected="true" <?php };?> value="AK">AK</option>
	<option <?php if ($_POST['state'] == 'AR') {?>selected="true" <?php };?> value="AR">AR</option>
	<option <?php if ($_POST['state'] == 'AZ') {?>selected="true" <?php };?> value="AZ">AZ</option>
	<option <?php if ($_POST['state'] == 'CA') {?>selected="true" <?php };?> value="CA">CA</option>
	<option <?php if ($_POST['state'] == 'CO') {?>selected="true" <?php };?> value="CO">CO</option>
	<option <?php if ($_POST['state'] == 'CT') {?>selected="true" <?php };?> value="CT">CT</option>
	<option <?php if ($_POST['state'] == 'DC') {?>selected="true" <?php };?> value="DC">DC</option>
	<option <?php if ($_POST['state'] == 'DE') {?>selected="true" <?php };?> value="DE">DE</option>
	<option <?php if ($_POST['state'] == 'FL') {?>selected="true" <?php };?> value="FL">FL</option>
	<option <?php if ($_POST['state'] == 'GA') {?>selected="true" <?php };?> value="GA">GA</option>
	<option <?php if ($_POST['state'] == 'HI') {?>selected="true" <?php };?> value="HI">HI</option>
	<option <?php if ($_POST['state'] == 'IA') {?>selected="true" <?php };?> value="IA">IA</option>
	<option <?php if ($_POST['state'] == 'ID') {?>selected="true" <?php };?> value="ID">ID</option>
	<option <?php if ($_POST['state'] == 'IL') {?>selected="true" <?php };?> value="IL">IL</option>
	<option <?php if ($_POST['state'] == 'IN') {?>selected="true" <?php };?> value="IN">IN</option>
	<option <?php if ($_POST['state'] == 'KS') {?>selected="true" <?php };?> value="KS">KS</option>
	<option <?php if ($_POST['state'] == 'KY') {?>selected="true" <?php };?> value="KY">KY</option>
	<option <?php if ($_POST['state'] == 'LA') {?>selected="true" <?php };?> value="LA">LA</option>
	<option <?php if ($_POST['state'] == 'MA') {?>selected="true" <?php };?> value="MA">MA</option>
	<option <?php if ($_POST['state'] == 'MD') {?>selected="true" <?php };?> value="MD">MD</option>
	<option <?php if ($_POST['state'] == 'ME') {?>selected="true" <?php };?> value="ME">ME</option>
	<option <?php if ($_POST['state'] == 'MI') {?>selected="true" <?php };?> value="MI">MI</option>
	<option <?php if ($_POST['state'] == 'MN') {?>selected="true" <?php };?> value="MN">MN</option>
	<option <?php if ($_POST['state'] == 'MO') {?>selected="true" <?php };?> value="MO">MO</option>
	<option <?php if ($_POST['state'] == 'MS') {?>selected="true" <?php };?> value="MS">MS</option>
	<option <?php if ($_POST['state'] == 'MT') {?>selected="true" <?php };?> value="MT">MT</option>
	<option <?php if ($_POST['state'] == 'NC') {?>selected="true" <?php };?> value="NC">NC</option>
	<option <?php if ($_POST['state'] == 'NE') {?>selected="true" <?php };?> value="NE">NE</option>
	<option <?php if ($_POST['state'] == 'NH') {?>selected="true" <?php };?> value="NH">NH</option>
	<option <?php if ($_POST['state'] == 'NJ') {?>selected="true" <?php };?> value="NJ">NJ</option>
	<option <?php if ($_POST['state'] == 'NM') {?>selected="true" <?php };?> value="NM">NM</option>
	<option <?php if ($_POST['state'] == 'NV') {?>selected="true" <?php };?> value="NV">NV</option>
	<option <?php if ($_POST['state'] == 'NY') {?>selected="true" <?php };?> value="NY">NY</option>
	<option <?php if ($_POST['state'] == 'ND') {?>selected="true" <?php };?> value="ND">ND</option>
	<option <?php if ($_POST['state'] == 'OH') {?>selected="true" <?php };?> value="OH">OH</option>
	<option <?php if ($_POST['state'] == 'OK') {?>selected="true" <?php };?> value="OK">OK</option>
	<option <?php if ($_POST['state'] == 'OR') {?>selected="true" <?php };?> value="OR">OR</option>
	<option <?php if ($_POST['state'] == 'PA') {?>selected="true" <?php };?> value="PA">PA</option>
	<option <?php if ($_POST['state'] == 'RI') {?>selected="true" <?php };?> value="RI">RI</option>
	<option <?php if ($_POST['state'] == 'SC') {?>selected="true" <?php };?> value="SC">SC</option>
	<option <?php if ($_POST['state'] == 'SD') {?>selected="true" <?php };?> value="SD">SD</option>
	<option <?php if ($_POST['state'] == 'TN') {?>selected="true" <?php };?> value="TN">TN</option>
	<option <?php if ($_POST['state'] == 'TX') {?>selected="true" <?php };?> value="TX">TX</option>
	<option <?php if ($_POST['state'] == 'UT') {?>selected="true" <?php };?> value="UT">UT</option>
	<option <?php if ($_POST['state'] == 'VT') {?>selected="true" <?php };?> value="VT">VT</option>
	<option <?php if ($_POST['state'] == 'VA') {?>selected="true" <?php };?> value="VA">VA</option>
	<option <?php if ($_POST['state'] == 'WA') {?>selected="true" <?php };?> value="WA">WA</option>
	<option <?php if ($_POST['state'] == 'WI') {?>selected="true" <?php };?> value="WI">WI</option>
	<option <?php if ($_POST['state'] == 'WV') {?>selected="true" <?php };?> value="WV">WV</option>
	<option <?php if ($_POST['state'] == 'WY') {?>selected="true" <?php };?> value="WY">WY</option>
</select>
<span class="error">* <?php echo $stateErr; ?></span>
<br><br>
Zip Code: <input type="text" name="zip" value="<?php echo $zip; ?>">
<span class="error">* <?php echo $zipErr; ?></span>
<br><br>
E-mail: <input type="email" name="email" value="<?php echo $email; ?>">
  <span class="error"><?php echo $emailErr; ?></span>
  <br><br>
Home Ownership:
<input type="radio" name="ownership" <?php if ($_POST['ownership'] == "own") {
    echo "checked";
}
?> value="own">Own
<input type="radio" name="ownership" <?php if ($_POST['ownership'] == "rent") {
    echo "checked";
}
?> value="rent">Rent
<input type="radio" name="ownership" <?php if ($_POST['ownership'] == "lease") {
    echo "checked";
}
?> value="lease">Lease
<input type="radio" name="ownership" <?php if ($_POST['ownership'] == "other") {
    echo "checked";
}
?> value="other">Other
<span class="error">* <?php echo $ownershipErr; ?></span>
<br><br>
Contact Time:
<input type="checkbox" name="contact_time[]" <?php if (IsChecked('contact_time','Morning')) echo "checked='checked'"; ?> value="Morning">Morning
<input type="checkbox" name="contact_time[]" <?php if (IsChecked('contact_time','Afternoon')) echo "checked='checked'"; ?> value="Afternoon">Afternoon
<input type="checkbox" name="contact_time[]" <?php if (IsChecked('contact_time','Evening')) echo "checked='checked'"; ?> value="Evening">Evening
<span class="error"><?php echo $contactTimeErr; ?></span>
<br><br>


  <input type="submit" name="submit" value="Submit">
  <input type="reset">
</form>

<?php

echo "<br>";
echo "<br>";

if (!empty($responseCode)) {
    if ($responseCode == 201) {
        echo "Success!";
    } else {
        echo "Error...";
        echo "<br>";
        echo "<br>";
        echo $response;
    }
}

?>

</body>
</html>