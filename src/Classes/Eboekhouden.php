<?php

namespace Dashed\DashedEcommerceEboekhouden\Classes;

use Illuminate\Support\Str;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedEcommerceCore\Models\Order;
use Dashed\DashedEcommerceEboekhouden\Models\EboekhoudenOrder;
use SoapClient;

class Eboekhouden
{
    public const SOAPBASEURL = "https://soap.e-boekhouden.nl/soap.asmx?WSDL";

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

        if (! $username || ! $securityCode1 || ! $securityCode2) {
            return;
        }

        try {
            $client = self::getSoapClient();
            $params = [
                "Username" => $username,
                "SecurityCode1" => $securityCode1,
                "SecurityCode2" => $securityCode2,
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
            "SessionID" => $sessionId,
        ];

        $response = $client->__soapCall("CloseSession", [$params]);
    }

    public static function isConnected($siteId = null)
    {
        if (! $siteId) {
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

    public static function pushOrder(EboekhoudenOrder $eboekhoudenOrder)
    {
        if ($eboekhoudenOrder->pushed) {
            return;
        }

        $sessionId = self::openSession($eboekhoudenOrder->order->site_id);
        $securityCode2 = Customsetting::get('eboekhouden_security_code_2', $eboekhoudenOrder->order->site_id);
        $GB = Customsetting::get('eboekhouden_grootboek_rekening', $eboekhoudenOrder->order->site_id);
        $DR = Customsetting::get('eboekhouden_debiteuren_rekening', $eboekhoudenOrder->order->site_id);

        if (! $eboekhoudenOrder->relation_id) {
            $otherOrders = Order::where('email', $eboekhoudenOrder->order->email)->get();
            foreach ($otherOrders as $otherOrder) {
                if ($otherOrder->eboekhoudenOrder && $otherOrder->eboekhoudenOrder->relation_id) {
                    $eboekhoudenOrder->relation_id = $otherOrder->eboekhoudenOrder->relation_id;
                    $eboekhoudenOrder->relation_code = $otherOrder->eboekhoudenOrder->relation_code;
                    $eboekhoudenOrder->save();
                }
            }
        }

        if (! $eboekhoudenOrder->relation_id) {
            try {
                $relationCode = $eboekhoudenOrder->order->site_id . Str::random(6);
                $client = self::getSoapClient();
                $params = [
                    "SessionID" => $sessionId,
                    "SecurityCode2" => $securityCode2,
                    "oRel" => [
                        'ID' => $eboekhoudenOrder->order->id,
                        'AddDatum' => now()->format('Y-m-d'),
                        'Gb_ID' => $GB,
                        'GeenEmail' => 0,
                        'NieuwsbriefgroepenCount' => 0,
                        'BP' => $eboekhoudenOrder->order->company_name ? 'B' : 'P',
                        'Code' => $relationCode,
                        'Bedrijf' => $eboekhoudenOrder->order->company_name ?: $eboekhoudenOrder->order->name,
                        'Geslacht' => $eboekhoudenOrder->order->gender == 'M' ? 'm' : ($eboekhoudenOrder->order->gender == 'F' ? 'v' : null),
                        'Contactpersoon' => $eboekhoudenOrder->order->company_name ? $eboekhoudenOrder->order->name : null,
                        'Adres' => $eboekhoudenOrder->order->street . ' ' . $eboekhoudenOrder->order->house_nr,
                        'Postcode' => $eboekhoudenOrder->order->zip_code,
                        'Plaats' => $eboekhoudenOrder->order->city,
                        'Land' => $eboekhoudenOrder->order->country,
                        'Adres2' => $eboekhoudenOrder->order->invoice_street . ' ' . $eboekhoudenOrder->order->invoice_house_nr,
                        'Postcode2' => $eboekhoudenOrder->order->invoice_zip_code,
                        'Plaats2' => $eboekhoudenOrder->order->invoice_city,
                        'Land2' => $eboekhoudenOrder->order->invoice_country,
                        'Telefoon' => $eboekhoudenOrder->order->phone_number,
                        'Email' => $eboekhoudenOrder->order->email,
                        'BTWNummer' => $eboekhoudenOrder->order->btw_id,
                    ],
                ];

                $response = $client->__soapCall("AddRelatie", [$params]);
                if (! isset($response->AddRelatieResult->Rel_ID) || ! $response->AddRelatieResult->Rel_ID) {
                    dd($response);
                } else {
                    $eboekhoudenOrder->relation_code = $relationCode;
                    $eboekhoudenOrder->relation_id = $response->AddRelatieResult->Rel_ID;
                    $eboekhoudenOrder->save();
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
        }

        if (! $eboekhoudenOrder->pushed && $eboekhoudenOrder->relation_id) {
            try {
                $invoiceLines = [];

                $totalAmountForVats = [];

                $totalPriceForProducts = 0;

                foreach ($eboekhoudenOrder->order->orderProducts as $orderProduct) {
                    if (! $orderProduct->product->is_bundle) {
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
                            if (! isset($totalAmountForVats[$vatRate])) {
                                $totalAmountForVats[$vatRate] = 0;
                            }
                            $totalAmountForVats[$vatRate] += ($orderProduct->price * $orderProduct->quantity);
                        }
                    }
                }

                $vatPercentageOfTotals = [];

                foreach ($totalAmountForVats as $percentage => $totalAmountForVat) {
                    $vatPercentageOfTotals[$percentage] = $totalAmountForVat > 0.00 ? ($totalAmountForVat / $totalPriceForProducts) * 100 : 0;
                }

                if ($eboekhoudenOrder->order->discount > 0.00) {
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

                            $discountForThisPercentage = ($eboekhoudenOrder->order->discount * ($vatPercentageOfTotal / 100));
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

                $invoiceNumberForEboekhouden = Str::substr($eboekhoudenOrder->order->psp_id ?: $eboekhoudenOrder->order->invoice_id, 0, 10);

                $params = [
                    "SessionID" => $sessionId,
                    "SecurityCode2" => $securityCode2,
                    "oMut" => [
                        'Factuurnummer' => $invoiceNumberForEboekhouden,
                        'MutatieNr' => $eboekhoudenOrder->order->invoice_id,
                        'RelatieCode' => $eboekhoudenOrder->relation_code,
                        'Soort' => 'FactuurVerstuurd',
                        'Rekening' => $DR,
                        'Datum' => $eboekhoudenOrder->order->created_at->format('Y-m-d'),
                        'MutatieRegels' => [
                            'cMutatieRegel' => $invoiceLines,
                        ],
                        'Betalingstermijn' => 30,
                        'Omschrijving' => 'Order: ' . $eboekhoudenOrder->order->invoice_id,
//                        'InExBTW' => $order->btw ? 'EX' : 'IN'
                        'InExBTW' => 'IN',
                    ],
                ];


                $response = $client->__soapCall("AddMutatie", [$params]);
                if (! isset($response->AddMutatieResult->Mutatienummer) || ! $response->AddMutatieResult->Mutatienummer) {
                    $eboekhoudenOrder->pushed = 2;
                    $eboekhoudenOrder->save();
                } else {
                    $eboekhoudenOrder->pushed = 1;
                    $eboekhoudenOrder->save();
                }
            } catch (\Exception $e) {
                dd($e->getMessage());
            }
        }
    }
}
