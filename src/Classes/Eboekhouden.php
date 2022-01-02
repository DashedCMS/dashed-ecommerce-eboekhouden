<?php

namespace Qubiqx\QcommerceEcommerceEboekhouden\Classes;

use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Qubiqx\QcommerceEcommerceCore\Models\Order;
use SoapClient;
use Illuminate\Support\Str;

class Eboekhouden
{
    const SOAPBASEURL = "https://soap.e-boekhouden.nl/soap.asmx?WSDL";

    public static function getSoapClient()
    {
        $client = new SoapClient(self::SOAPBASEURL);
        return $client;
    }

    public static function openSession($siteId)
    {
        $username = Customsetting::get('eboekhouden_username', $siteId);
        $securityCode1 = Customsetting::get('eboekhouden_security_code_1', $siteId);
        $securityCode2 = Customsetting::get('eboekhouden_security_code_2', $siteId);

        if (!$username || !$securityCode1 || !$securityCode2) {
            return;
        }

        try {
            $client = self::getSoapClient();
            $params = [
                "Username" => $username,
                "SecurityCode1" => $securityCode1,
                "SecurityCode2" => $securityCode2
            ];

            $response = $client->__soapCall("OpenSession", [$params]);
            $sessionId = $response->OpenSessionResult->SessionID;

            return $sessionId;
        } catch (\Exception $e) {
        }

        return;
    }

    public static function closeSession($sessionId)
    {
        $client = self::getSoapClient();

        $params = [
            "SessionID" => $sessionId
        ];

        $response = $client->__soapCall("CloseSession", [$params]);
    }

    public static function isConnected($siteId = null)
    {
        if (!$siteId) {
            $siteId = Sites::getActive();
        }

        try {
            $sessionId = self::openSession($siteId);
        } catch (\Exception $e) {
            $sessionId = null;
        }

        if ($sessionId) {
            self::closeSession($sessionId);
            return true;
        }

        return false;
    }

    public static function pushOrder($order)
    {
        $sessionId = self::openSession($order->site_id);
        $securityCode2 = Customsetting::get('eboekhouden_security_code_2', $order->site_id);
        $GB = Customsetting::get('eboekhouden_grootboek_rekening', $order->site_id);
        $DR = Customsetting::get('eboekhouden_debiteuren_rekening', $order->site_id);

        if (!$order->eboekhouden_order_connection_id) {
            $otherOrder = Order::whereNotNull('eboekhouden_order_connection_id')->where('email', $order->email)->first();
            if ($otherOrder) {
                $order->eboekhouden_order_connection_id = $otherOrder->eboekhouden_order_connection_id;
                $order->save();
            }
        }

        if (!$order->eboekhouden_order_connection_id) {
            try {
                $relationCode = $order->site_id . Str::random(6);
                $client = self::getSoapClient();
                $params = [
                    "SessionID" => $sessionId,
                    "SecurityCode2" => $securityCode2,
                    "oRel" => [
                        'ID' => $order->id,
                        'AddDatum' => now()->format('Y-m-d'),
                        'Gb_ID' => $GB,
                        'GeenEmail' => 0,
                        'NieuwsbriefgroepenCount' => 0,
                        'BP' => $order->company_name ? 'B' : 'P',
                        'Code' => $relationCode,
                        'Bedrijf' => $order->company_name ?: $order->name,
                        'Geslacht' => $order->gender == 'M' ? 'm' : ($order->gender == 'F' ? 'v' : null),
                        'Contactpersoon' => $order->company_name ? $order->name : null,
                        'Adres' => $order->street . ' ' . $order->house_nr,
                        'Postcode' => $order->zip_code,
                        'Plaats' => $order->city,
                        'Land' => $order->country,
                        'Adres2' => $order->invoice_street . ' ' . $order->invoice_house_nr,
                        'Postcode2' => $order->invoice_zip_code,
                        'Plaats2' => $order->invoice_city,
                        'Land2' => $order->invoice_country,
                        'Telefoon' => $order->phone_number,
                        'Email' => $order->email,
                        'BTWNummer' => $order->btw_id,
                    ]
                ];

                $response = $client->__soapCall("AddRelatie", [$params]);
                if (!isset($response->AddRelatieResult->Rel_ID) || !$response->AddRelatieResult->Rel_ID) {
                    dd($response);
                } else {
                    $eboekhoudenOrderConnection = new EboekhoudenOrderConnection();
                    $eboekhoudenOrderConnection->relation_code = $relationCode;
                    $eboekhoudenOrderConnection->relation_id = $response->AddRelatieResult->Rel_ID;
                    $eboekhoudenOrderConnection->save();

                    $order->eboekhouden_order_connection_id = $eboekhoudenOrderConnection->id;
                    $order->save();

                    Order::where('email', $order->email)->update([
                        'eboekhouden_order_connection_id' => $eboekhoudenOrderConnection->id,
                    ]);
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
        }

        if (!$order->pushed_to_eboekhouden && $order->eboekhouden_order_connection_id) {
            try {
                $invoiceLines = [];

                $totalAmountForVats = [];

                $totalPriceForProducts = 0;

                foreach ($order->orderProducts as $orderProduct) {
                    $vatRate = $orderProduct->vat_rate;

                    if ($vatRate == 21) {
                        $vatCode = 'HOOG_VERK_21';
                    } elseif ($vatRate == 9) {
                        $vatCode = 'LAAG_VERK_9';
                    } elseif ($vatRate > 0) {
                        $vatCode = 'AFW_VERK';
                    } else {
                        $vatCode = 'GEEN';
                    }

                    $totalPriceForProducts += $orderProduct->price;

                    $invoiceLines[] = [
                        'BedragInvoer' => number_format($orderProduct->priceWithoutDiscount, 2),
                        'BedragExclBTW' => number_format($orderProduct->priceWithoutDiscount - $orderProduct->vatWithoutDiscount, 2),
                        'BedragBTW' => number_format($orderProduct->vatWithoutDiscount, 2),
                        'BedragInclBTW' => number_format($orderProduct->priceWithoutDiscount, 2),
                        'BTWCode' => $vatCode,
                        'BTWPercentage' => number_format($vatRate, 2),
                        'TegenrekeningCode' => $GB,
                        'KostenplaatsID' => '',
                    ];
                    if ($orderProduct->vat_rate > 0) {
                        if (!isset($totalAmountForVats[$vatRate])) {
                            $totalAmountForVats[$vatRate] = 0;
                        }
                        $totalAmountForVats[$vatRate] += ($orderProduct->price * $orderProduct->quantity);
                    }
                }

                $vatPercentageOfTotals = [];

                foreach ($totalAmountForVats as $percentage => $totalAmountForVat) {
                    $vatPercentageOfTotals[$percentage] = $totalAmountForVat > 0.00 ? ($totalAmountForVat / $totalPriceForProducts) * 100 : 0;
                }

                if ($order->discount > 0.00) {
                    foreach ($vatPercentageOfTotals as $percentage => $vatPercentageOfTotal) {
                        if ($vatPercentageOfTotal) {
                            if ($percentage == 21) {
                                $vatCode = 'HOOG_VERK_21';
                            } elseif ($percentage == 9) {
                                $vatCode = 'LAAG_VERK_9';
                            } elseif ($percentage > 0) {
                                $vatCode = 'AFW_VERK';
                            } else {
                                $vatCode = 'GEEN';
                            }

                            $discountForThisPercentage = ($order->discount * ($vatPercentageOfTotal / 100));
                            $taxTotal = ($discountForThisPercentage / (100 + $percentage) * $percentage);

                            $amountExclVat = number_format(0 - $discountForThisPercentage + $taxTotal, 2);
                            $amountIncVat = number_format(0 - $discountForThisPercentage, 2);
                            $amountVat = number_format(0 - $taxTotal, 2);

                            if (($amountExclVat + $amountVat) != $amountIncVat) {
                                $vatDifference = ($amountExclVat + $amountVat) - $amountIncVat;
                                $amountExclVat -= $vatDifference;
                            }

                            $invoiceLines[] = [
                                'BedragInvoer' => number_format(0 - $discountForThisPercentage, 2),
                                'BedragExclBTW' => $amountExclVat,
                                'BedragBTW' => $amountVat,
                                'BedragInclBTW' => $amountIncVat,
                                'BTWCode' => $vatCode,
                                'BTWPercentage' => number_format($percentage, 2),
                                'TegenrekeningCode' => $GB,
                                'KostenplaatsID' => '',
                            ];
                        }
                    }
                }

                $client = self::getSoapClient();

                $invoiceNumberForEboekhouden = $order->psp_id ?: $order->invoice_id;
                if ($order->psp == 'paynl') {
                    $invoiceNumberForEboekhouden = Str::substr($invoiceNumberForEboekhouden, 0, 10);
                }

                $params = [
                    "SessionID" => $sessionId,
                    "SecurityCode2" => $securityCode2,
                    "oMut" => [
                        'Factuurnummer' => $invoiceNumberForEboekhouden,
                        'MutatieNr' => $order->invoice_id,
                        'RelatieCode' => $order->eboekhoudenOrderConnection->relation_code,
                        'Soort' => 'FactuurVerstuurd',
                        'Rekening' => $DR,
                        'Datum' => $order->created_at->format('Y-m-d'),
                        'MutatieRegels' => [
                            'cMutatieRegel' => $invoiceLines
                        ],
                        'Betalingstermijn' => 30,
                        'Omschrijving' => 'Order: ' . $order->invoice_id,
//                        'InExBTW' => $order->btw ? 'EX' : 'IN'
                        'InExBTW' => 'IN'
                    ]
                ];


                $response = $client->__soapCall("AddMutatie", [$params]);
                if (!isset($response->AddMutatieResult->Mutatienummer) || !$response->AddMutatieResult->Mutatienummer) {
                    $order->pushed_to_eboekhouden = 2;
                    $order->save();
                } else {
                    $order->pushed_to_eboekhouden = 1;
                    $order->save();
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
        }
    }
}
