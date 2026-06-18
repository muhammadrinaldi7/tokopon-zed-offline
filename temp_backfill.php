<?php
$phones = \App\Models\SellPhone::whereNull('business_unit_id')->get();
foreach ($phones as $p) {
    if ($p->handled_by) {
        $user = \App\Models\User::find($p->handled_by);
        if ($user) {
            $p->business_unit_id = $user->getActiveBusinessUnitId();
            $p->save();
        }
    } else {
        $p->business_unit_id = 1; // Default
        $p->save();
    }
}
echo "Done";
