<?php
$g = \App\Models\User::where('name', 'like', '%Ganesh%')->first();
if ($g) {
    echo json_encode($g->getDirectPermissions()->pluck('name'));
} else {
    echo "Ganesh not found";
}
