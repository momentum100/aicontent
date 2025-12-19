<nav class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-10 items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-700 hover:text-gray-900">Dashboard</a>
                <a href="{{ route('profile.edit') }}" class="text-sm text-gray-500 hover:text-gray-700">Profile</a>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>
