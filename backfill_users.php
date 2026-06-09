<?php
use App\Models\Barbero;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$barberos = Barbero::all();
$count = 0;
foreach($barberos as $barbero) {
    // Check if user already exists
    $exists = User::where('barbero_id', $barbero->id)->exists();
    if (!$exists) {
        $emailBase = ($barbero->telefono ? preg_replace('/[^0-9]/', '', $barbero->telefono) : strtolower(str_replace(' ', '', $barbero->nombre))) . '@empleado.com';
        
        // Prevent duplicate email just in case
        $finalEmail = $emailBase;
        $i = 1;
        while(User::where('email', $finalEmail)->exists()) {
            $finalEmail = str_replace('@', $i . '@', $emailBase);
            $i++;
        }

        User::create([
            'name' => $barbero->nombre,
            'email' => $finalEmail,
            'password' => Hash::make('12345678'),
            'barberia_id' => $barbero->barberia_id,
            'rol' => 'empleado',
            'barbero_id' => $barbero->id,
        ]);
        $count++;
    }
}
echo "Cuentas generadas: $count\n";
