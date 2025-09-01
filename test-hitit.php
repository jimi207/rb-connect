<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$url = "https://rba-stage.crane.aero/craneota/CraneOTAService";


try {
    $client = new SoapClient(null, array(
        'location' => $url,
        'uri' => "http://impl.soap.ws.crane.hititcs.com/",
        'trace' => 1,
        'features' => SOAP_SINGLE_ELEMENT_ARRAYS
    ));

    // Example of calling a SOAP function


    /*$params = array(
        'AirBookingReadRequest' => array(
            'clientInformation' => array(
                'clientIP' => '129.0.0.1',
                'member' => false,
                'password' => '$Happy1994',
                'userName' => 'A2920_BWN54'
            ),
            'bookingReferenceID' => array(
                'ID' => '13KT0A'
            )
        )
    );*/


    //$node = array();


    $nodeInfo[] = new SoapVar("129.0.0.1", XSD_STRING, null, null, 'clientIP');
    $nodeInfo[] = new SoapVar('false', XSD_STRING, null, null, 'member');
    $nodeInfo[] = new SoapVar("A2920_BWN54", XSD_STRING, null, null, 'userName');
    $nodeInfo[] = new SoapVar('$Happy1994', XSD_STRING, null, null, 'password');

    //$nodeClient[] = new SoapVar($nodeInfo, SOAP_ENC_OBJECT, null, null, 'ID');


    $nodeID[] = new SoapVar("13KT82", XSD_STRING, null, null, 'ID');
    //$nodeBooking[] = new SoapVar($nodeID, SOAP_ENC_OBJECT, null, null, 'ID');

    $node[] = new SoapVar($nodeInfo, SOAP_ENC_OBJECT, null, null, 'clientInformation');
    $node[] = new SoapVar($nodeID, SOAP_ENC_OBJECT, null, null, 'bookingReferenceID');

    $params = new SoapVar($node, SOAP_ENC_OBJECT, null, null, 'AirBookingReadRequest');

    //$params = [new SoapParam([new SoapParam('ABC', 'ClientInformation')], 'AirBookingReadRequest')];


    $response = $client->__soapCall("ReadBooking", array($params));
    //echo json_encode($response);

    $json = json_encode($response);
    $data = json_decode($json);
    $ssr = $data->airBookingList->airReservation->specialRequestDetails->specialServiceRequestList;
    $coupon = $data->airBookingList->ticketInfo->ticketItemList;

    $email = $data->airBookingList->airReservation->contactInfoList->email->email;

    $airTravelerList = $data->airBookingList->airReservation->airTravelerList;

    $flightSegment = $data->airBookingList->airReservation->airItinerary->bookOriginDestinationOptions->bookOriginDestinationOptionList;

    if (!is_object($airTravelerList)) {
        $lastname = $data->airBookingList->airReservation->airTravelerList[0]->personName->surname;
    } else {
        $lastname = $data->airBookingList->airReservation->airTravelerList->personName->surname;
    }

    echo "total coupon: " . count($coupon) . '<br />';

    $couponList = [];
    foreach ($coupon as $object) {
        $couponList[] = $object->couponInfoList;
    }
    echo "total couponList: " . count($couponList) . '<br />';
    //print_r($couponList);

    //$arrayobj->append(array('five', 'six'));
    $couponFormattedList = [];
    foreach ($couponList as $object) {
        foreach ($object as $objectInner) {
            $strSurname = "";
            $strDepartureDateTimeUTC = "";
            $strFlightNumber = "";
            $strSeat = "";
            if (isset($objectInner->airTraveler->personName)) {
                $strSurname = $objectInner->airTraveler->personName->surname;
            }
            if (isset($objectInner->couponFlightSegment->flightSegment)) {
                $strDepartureDateTimeUTC = $objectInner->couponFlightSegment->flightSegment->departureDateTimeUTC;
            }
            if (isset($objectInner->couponFlightSegment->flightSegment)) {
                $strFlightNumber = $objectInner->couponFlightSegment->flightSegment->flightNumber;
            }
            if (isset($objectInner->seatName)) {
                $strSeat = $objectInner->seatName;
            }
            if (isset($objectInner->ticketDocumentNbr)) {
                if ($objectInner->ticketDocumentNbr != null) {
                    $couponFormattedList[] = ['ticketNumber' => $objectInner->ticketDocumentNbr, 'surname' => $strSurname, 'departureDateTimeUTC' => $strDepartureDateTimeUTC, 'flightNumber' => $strFlightNumber, 'seat' => $strSeat];
                }
            }
        }

        //$couponFormattedList[] = ['ticketNumber' => $object->ticketDocumentNbr];
    }

    echo '<br />CPN Formatted: <br />';
    echo json_encode(($couponFormattedList));

    //echo '<br />CPN: <br />';
    //echo json_encode(($couponList));

    /*foreach ($couponList as $object) {
        if(is_object($object->airTraveler)){
            print_r($object->airTraveler);
        }
        
    }*/

    $travelers = [];

    $i = 0;
    foreach ($airTravelerList as $object) {
        $i++;
        $travelers[] = ["seq" => $i, "last_name" => $object->personName->surname];
    }

    echo '<br />Travellers: <br />';
    print_r($travelers);

    $ffp_members = [];

    $ssrList = [];

    foreach ($ssr as $object) {

        //echo "<br />traveler seq: " . $object->airTravelerSequence;
        $exp = "";
        $seat = "";
        if ($object->SSR->code === 'FQTV') {
            $exp = $object->SSR->explanation;
        }
        if ($object->SSR->code === 'SEAT') {
            $seat = $object->SSR->explanation;
        }

        $ssrList[] = ["seq" => $object->airTravelerSequence, "fqtv" => $exp, "seat" => $seat];

        /*for ($i = 0; $i < count($travelers); $i++) {

            if ($object->airTravelerSequence == $travelers[$i]["seq"]) {
                $ffp_members[] = ["fqtv" => substr($exp,12,strlen($exp)), "last_name" => $travelers[$i]["last_name"], "seat" => $seat];
            }
        }*/
        //$ffp_members[] = ["fqtv" => $object->SSR->explanation, "last_name" => $object->airTravelerSequence];
        //echo 'rs: ' . $object->SSR->explanation;
        //echo "<br />traveler seq: " . $object->airTravelerSequence . '<br />';

    }
    echo '<br />SSR: <br />';
    echo json_encode(($ssrList));

    /* foreach ($ssr as $object) {
         if ($object->SSR->code === 'FQTV') {
             echo 'exp: ' . $object->SSR->explanation;
             break;
         }
     }*/

    $flights = [];

    echo '<br />Flights: <br />';

    foreach ($flightSegment as $object) {
        $flights[] = ["departureDateTimeUTC" => substr($object->bookFlightSegmentList->flightSegment->departureDateTimeUTC, 0, 16), "flightNumber" => $object->bookFlightSegmentList->flightSegment->flightNumber];
        //if ($object->SSR->code === 'FQTV') {
        echo 'ddate ' . substr($object->bookFlightSegmentList->flightSegment->departureDateTimeUTC, 0, 16);
        echo 'flt ' . $object->bookFlightSegmentList->flightSegment->flightNumber;
        //break;
        //}
    }
    echo '<br />';
    //print_r($flights);

    foreach ($flights as $object) {
        //echo $object["flightNumber"];
        //$responseObj->flights->flight["departure_date_utc"] = $object["departureDateTimeUTC"];
    }


    echo '<br />email: ' . $email;
    echo 'lastname pax1: ' . $lastname;
    //echo 'fltsegment: ' . $flightSegment;
    //echo 'fltsegmentcount: ' . count($flightSegment);

    //print_r($ssr);

} catch (SoapFault $e) {
    echo "REQUEST:\n" . htmlentities($client->__getLastRequest()) . "\n";
    echo "SOAP Fault: " . $e->getMessage();
}