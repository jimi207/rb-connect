<?php

class Hitit
{
    public $pnr;
    public $last_name;
    public $fqtv;

    public $email;

    public $coupons = [];
    public $members = [];

    function getProfile()
    {

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
            $nodeInfo[] = new SoapVar('$Happy1996', XSD_STRING, null, null, 'password');

            //$nodeClient[] = new SoapVar($nodeInfo, SOAP_ENC_OBJECT, null, null, 'ID');


            $nodeID[] = new SoapVar($this->pnr, XSD_STRING, null, null, 'ID');
            //$nodeBooking[] = new SoapVar($nodeID, SOAP_ENC_OBJECT, null, null, 'ID');

            $node[] = new SoapVar($nodeInfo, SOAP_ENC_OBJECT, null, null, 'clientInformation');
            $node[] = new SoapVar($nodeID, SOAP_ENC_OBJECT, null, null, 'bookingReferenceID');

            $params = new SoapVar($node, SOAP_ENC_OBJECT, null, null, 'AirBookingReadRequest');

            //$params = [new SoapParam([new SoapParam('ABC', 'ClientInformation')], 'AirBookingReadRequest')];


            $response = $client->__soapCall("ReadBooking", array($params));
            //echo json_encode($response);
            //exit();


            $json = json_encode($response);
            $data = json_decode($json);
            $ssr = $data->airBookingList->airReservation->specialRequestDetails->specialServiceRequestList;
            $this->email = $data->airBookingList->airReservation->contactInfoList->email->email;
            $coupon = $data->airBookingList->ticketInfo->ticketItemList;
            $lastname = "";
            $fullName = "";


            foreach ($ssr as $object) {
                if ($object->SSR->code === 'FQTV') {
                    //echo 'exp: ' . $object->SSR->explanation;
                    $this->fqtv = $object->SSR->explanation;
                    break;
                }
            }

            $airTravelerList = $data->airBookingList->airReservation->airTravelerList;

            $travelers = [];
            $i = 0;

            if (is_array($airTravelerList)) {
                foreach ($airTravelerList as $object) {
                    $i++;
                    $travelers[] = ["seq" => $i, "last_name" => $object->personName->surname];
                }
            } else {
                $travelers[] = ["seq" => 1, "last_name" => $airTravelerList->personName->surname];
            }



            $couponList = [];
            if (is_array($coupon)) {
                foreach ($coupon as $object) {
                    $couponList[] = $object->couponInfoList;
                }
            } else {
                $couponList[] = $coupon->couponInfoList;
            }
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
            }

            $this->coupons = $couponFormattedList;


            $ffp_members = [];
            foreach ($ssr as $object) {
                if ($object->SSR->code === 'FQTV') {
                    for ($i = 0; $i < count($travelers); $i++) {
                        if ($object->airTravelerSequence == $travelers[$i]["seq"]) {
                            $exp = $object->SSR->explanation;
                            $ffp_members[] = ["fqtv" => substr($exp, 12, strlen($exp)), "last_name" => $travelers[$i]["last_name"]];
                        }
                    }
                }
            }

            $this->members = $ffp_members;

            /*$flightSegment = $data->airBookingList->airReservation->airItinerary->bookOriginDestinationOptions->bookOriginDestinationOptionList;

            $flight = [];

            foreach ($flightSegment as $object) {
                $flight[] = ["departureDateTimeUTC" => substr($object->bookFlightSegmentList->flightSegment->departureDateTimeUTC, 0, 16), "flightNumber" => $object->bookFlightSegmentList->flightSegment->flightNumber];
            }

            $this->flights = $flight;*/

            return $data;
        } catch (SoapFault $e) {
            //echo "REQUEST:\n" . htmlentities($client->__getLastRequest()) . "\n";
            //echo "SOAP Fault: " . $e->getMessage();
        }
        //return "Gold";
    }
}

/*$hitit = new Hitit();
$hitit->pnr = "13KT0A";
$hitit->last_name = "Smith";
$hitit->getProfile();
echo "tier: " . $hitit->fqtv;*/
//print_r($result);

//$xml = simplexml_load_string($result);
//$xml->registerXPathNamespace("soap", "http://schemas.xmlsoap.org/soap/envelope");
//print_r($xml->xpath('//soap:Body'));



//print_r($result);
