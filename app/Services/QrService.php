<?php

namespace App\Services;

use App\Models\Emoji;
use App\Models\IpQr;
use App\Models\Restaurant;
use Endroid\QrCode\Encoding\Encoding;
use Illuminate\Support\Facades\Storage;
// use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrService
{
    // to show paginate Qr
    public function paginate($num)
    {
        $qrs = IpQr::latest()->paginate($num);
        return $qrs;
    }

    // to create Qr
    public function create($data)
    {
        $data_val = $this->filterNullValues($data);
        $qr = IpQr::create($data);

        $name = $data_val['name'] ?? null;
        $email = $data_val['email'] ?? null;
        $whatsapp_phone = $data_val['whatsapp_phone'] ?? null;
        $facebook_url = $data_val['facebook_url'] ?? null;
        $instagram_url = $data_val['instagram_url'] ?? null;
        $restaurant_url = $data_val['restaurant_url'] ?? null;
        $phone = $data_val['phone'] ?? null;
        $website = $data_val['website'] ?? null;

        $vCard = "BEGIN:VCARD\n";
        $vCard .= "VERSION:3.0\n";
        $vCard .= "FN:$name\n";

        if (!empty($arr['email']))
            $vCard .= "EMAIL:$email\n";

        if (!empty($whatsapp_phone))
            $vCard .= "TEL;TYPE=CELL:$whatsapp_phone\n";

        if (!empty($phone))
            $vCard .= "TEL:$phone\n";

        if (!empty($facebook_url))
            $vCard .= "X-SOCIALPROFILE;TYPE=facebook:$facebook_url\n";

        if (!empty($instagram_url))
            $vCard .= "X-SOCIALPROFILE;TYPE=instagram:$instagram_url\n"; // رابط إنستغرام

        if (!empty($address))
            $vCard .= "ADR;TYPE=HOME:;;$address\n";

        if (!empty($restaurant_url))
            $vCard .= "URL:$restaurant_url\n"; // رابط الموقع
        if (!empty($website))
            $vCard .= "URL:$website\n"; // رابط الموقع
        $vCard .= "END:VCARD";

        // $ad = json_encode($data_val);
        // $ad = json_encode($data_val, JSON_UNESCAPED_UNICODE);
        $qrCode = new QrCode($vCard);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setSize(500);
        $qrCode->setMargin(10);
        $writer = new PngWriter();
        $qrCodePath = 'public/qr_code_offline/' . $qr['id'] . '.png';
        $result = $writer->write($qrCode);
        Storage::put($qrCodePath, $result->getString());
        // $qrCode = QrCode::format('png')->size(500)->generate("$ad");
        // $qrCodePath = 'public/qr_code_offline/' . $qr['id'] . '.png';
        // Storage::put($qrCodePath, $qrCode);
        $qr->qr_code = $qrCodePath;
        $qr->save();
        return $qr;
    }

    // to update Qr
    public function update($id,$qr_code,$data)
    {
        // IpQr::whereId($id)->update($data);
        Storage::delete($qr_code);
        $data_val = $this->filterNullValues($data);

        $name = $data_val['name'] ?? null;
        $email = $data_val['email'] ?? null;
        $whatsapp_phone = $data_val['whatsapp_phone'] ?? null;
        $facebook_url = $data_val['facebook_url'] ?? null;
        $instagram_url = $data_val['instagram_url'] ?? null;
        $restaurant_url = $data_val['restaurant_url'] ?? null;
        $phone = $data_val['phone'] ?? null;
        $website = $data_val['website'] ?? null;

        $vCard = "BEGIN:VCARD\n";
        $vCard .= "VERSION:3.0\n";
        $vCard .= "FN:$name\n";

        if (!empty($arr['email']))
            $vCard .= "EMAIL:$email\n";

        if (!empty($whatsapp_phone))
            $vCard .= "TEL;TYPE=CELL:$whatsapp_phone\n";

        if (!empty($phone))
            $vCard .= "TEL:$phone\n";

        if (!empty($facebook_url))
            $vCard .= "X-SOCIALPROFILE;TYPE=facebook:$facebook_url\n";

        if (!empty($instagram_url))
            $vCard .= "X-SOCIALPROFILE;TYPE=instagram:$instagram_url\n"; // رابط إنستغرام

        if (!empty($address))
            $vCard .= "ADR;TYPE=HOME:;;$address\n";

        if (!empty($restaurant_url))
            $vCard .= "URL:$restaurant_url\n"; // رابط الموقع
        if (!empty($website))
            $vCard .= "URL:$website\n"; // رابط الموقع
        $vCard .= "END:VCARD";
        // $ad = json_encode($data_val);
        // $ad = json_encode($data_val, JSON_UNESCAPED_UNICODE);
        $qrCode = new QrCode($vCard);
        $qrCode->setEncoding(new Encoding('UTF-8'));
        $qrCode->setSize(500);
        $qrCode->setMargin(10);
        $writer = new PngWriter();
        $qrCodePath = 'public/qr_code_offline/' . $id . '.png';
        $result = $writer->write($qrCode);
        Storage::put($qrCodePath, $result->getString());
        // $qrCode = QrCode::format('png')->size(500)->generate("$ad");
        // $qrCodePath = 'public/qr_code_offline/' . $id . '.png';
        // Storage::put($qrCodePath, $qrCode);

        $ip_qr = IpQr::whereId($id)->first();

        $ip_qr->name = $data['name'] ?? null;
        $ip_qr->email = $data['email'] ?? null;
        $ip_qr->restaurant_url = $data['restaurant_url'] ?? null;
        $ip_qr->website = $data['website'] ?? null;
        $ip_qr->phone = $data['phone'] ?? null;
        $ip_qr->facebook_url = $data['facebook_url'] ?? null;
        $ip_qr->instagram_url = $data['instagram_url'] ?? null;
        $ip_qr->whatsapp_phone = $data['whatsapp_phone'] ?? null;
        $ip_qr->address = $data['address'] ?? null;

        $ip_qr->qr_code = $qrCodePath;
        $ip_qr->save();
        return $ip_qr;
    }

    // to show a Emoji
    public function show(string $id)
    {
        $qr = IpQr::whereRestaurantId($id)->first();
        return $qr;
    }

    public function filterNullValues($array) {
        return array_filter($array, function ($value) {
            return !is_null($value);
        });
    }
}
