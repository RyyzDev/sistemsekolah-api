<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'registration_number' => $this->registration_number,
            'registration_type' => $this->registration_type,
            'registration_path' => $this->registration_path,
            'registration_date' => $this->registration_date,
            'status' => $this->status,
            'full_name' => $this->full_name,
            'nickname' => $this->nickname,
            'nisn' => $this->nisn,
            'nik' => $this->nik,
            'no_kk' => $this->no_kk,
            'no_akta_lahir' => $this->no_akta_lahir,
            'gender' => $this->gender,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date,
            'religion' => $this->religion,
            'citizenship' => $this->citizenship,
            'nationality' => $this->nationality,
            'address' => $this->address,
            'rt' => $this->rt,
            'rw' => $this->rw,
            'dusun' => $this->dusun,
            'kelurahan' => $this->kelurahan,
            'kecamatan' => $this->kecamatan,
            'kabupaten_kota' => $this->kabupaten_kota,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'residence_type' => $this->residence_type,
            'transportation' => $this->transportation,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'height' => $this->height,
            'weight' => $this->weight,
            'blood_type' => $this->blood_type,
            'special_needs' => $this->special_needs,
            'special_needs_description' => $this->special_needs_description,
            'disease_history' => $this->disease_history,
            'child_number' => $this->child_number,
            'total_siblings' => $this->total_siblings,
            'hobby' => $this->hobby,
            'ambition' => $this->ambition,
            // Boolean fields
            'kps_pkh_recipient' => (bool) $this->kps_pkh_recipient,
            'kps_pkh_number' => $this->kps_pkh_number,
            'kip_recipient' => (bool) $this->kip_recipient,
            'kip_number' => $this->kip_number,
            'pip_eligible' => (bool) $this->pip_eligible,
            'kks_number' => $this->kks_number,
            // Data sekolah sebelumnya
            'previous_school_name' => $this->previous_school_name,
            'previous_school_npsn' => $this->previous_school_npsn,
            'previous_school_address' => $this->previous_school_address,
            'ijazah_number' => $this->ijazah_number,
            'ijazah_date' => $this->ijazah_date,
            'skhun_number' => $this->skhun_number,
            'photo' => $this->photo,
            // // Timestamps
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->deleted_at,

            // RELATIONSHIPS 
            'user' => new UserResource($this->whenLoaded('user')),
            'parents' => ParentResource::collection($this->whenLoaded('parents')),
            'achievements' => AchievementResource::collection($this->whenLoaded('achievements')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}