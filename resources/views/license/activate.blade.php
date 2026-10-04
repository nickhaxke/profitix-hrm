<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate License — Profitix HRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-950 to-slate-900 min-h-screen flex items-center justify-center font-[Inter]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white text-2xl font-bold mb-4 shadow-xl">P</div>
            <h1 class="text-3xl font-bold text-white">Profitix HRM</h1>
            <p class="text-blue-300 mt-1">License Activation Required</p>
        </div>
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-8">
            @if($license && $license->isActive())
            <div class="text-center">
                <i class="fas fa-check-circle text-green-400 text-4xl mb-3"></i>
                <h2 class="text-xl font-semibold text-white">License Active</h2>
                <p class="text-blue-200 mt-2">Key: {{ $license->license_key }}</p>
                <p class="text-blue-200">Expires: {{ $license->expires_at?->format('M j, Y') ?? 'Never' }}</p>
                <a href="{{ route('dashboard') }}" class="mt-4 inline-block px-6 py-2 bg-blue-600 text-white rounded-lg">Go to Dashboard</a>
            </div>
            @else
            <h2 class="text-xl font-semibold text-white mb-2">Enter License Token</h2>
            <p class="text-blue-200 text-sm mb-6 bg-blue-900/30 p-4 rounded-xl border border-blue-800/50">
                <i class="fas fa-info-circle mr-2 text-blue-400"></i>
                Please contact your system administrator or <strong class="text-white">make a payment</strong> to receive a valid License Key to activate and continue using the system.
            </p>
            
            @if($errors->any())
            <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg text-red-200 text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('license.process') }}">@csrf
                <input type="text" name="license_key" required placeholder="PRFTX-XXXXX-XXXXX-XXXXX-XXXXX" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 text-center tracking-wider font-mono mb-4">
                <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">Activate License</button>
            </form>
            @endif
        </div>
    </div>
</body>
</html>
