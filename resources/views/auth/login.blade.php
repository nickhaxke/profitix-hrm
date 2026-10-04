<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Profitix HRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config={
            theme:{
                extend:{
                    fontFamily:{sans:['Inter','sans-serif']},
                    colors:{
                        brand: { 50:'#f4f9f0', 100:'#e5f3dc', 200:'#cbe7bb', 300:'#a8d58f', 400:'#8cc63f', 500:'#75b32e', 600:'#5c9124', 700:'#48711e', 800:'#3c5a1b', 900:'#334a19' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#1e282c] min-h-screen flex items-center justify-center font-sans">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-white text-2xl font-bold mb-4 shadow-xl shadow-brand-500/30">P</div>
            <h1 class="text-3xl font-bold text-white">Profitix HRM</h1>
            <p class="text-brand-300 mt-1">Biometric Attendance & HR Management</p>
        </div>

        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-xl font-semibold text-white mb-6">Sign in to your account</h2>

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-500/20 border border-red-500/30 text-red-200 rounded-lg text-sm">
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-brand-200 mb-1.5">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-brand-200 mb-1.5">Password</label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:ring-2 focus:ring-brand-500 focus:border-transparent outline-none transition">
                </div>
                <button type="submit"
                    class="w-full py-3 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white font-semibold rounded-xl shadow-lg shadow-brand-500/30 transition-all duration-200 hover:shadow-xl hover:shadow-brand-500/40">
                    <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">&copy; {{ date('Y') }} Profitix HRM. All rights reserved.</p>
    </div>
</body>
</html>
