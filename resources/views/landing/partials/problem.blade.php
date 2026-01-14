<!-- Problem Section -->
<section class="py-20 bg-gradient-to-b from-white to-gray-50 relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-100 to-orange-100 rounded-full text-sm font-bold text-red-700 mb-6">
                {{ $translations['problem']['badge'] }}
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 mb-6">
                {{ $translations['problem']['title'] }}
            </h2>
        </div>

        <!-- Problem Cards -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            @foreach($translations['problem']['items'] as $index => $item)
                <div class="animate-on-scroll" style="animation-delay: {{ $index * 0.1 }}s;">
                    <div class="bg-white rounded-2xl p-6 border-2 border-red-100 hover:border-red-200 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 h-full">
                        <!-- Icon -->
                        <div class="w-14 h-14 bg-gradient-to-br from-red-50 to-orange-50 rounded-2xl flex items-center justify-center text-3xl mb-4">
                            {{ $item['icon'] }}
                        </div>

                        <!-- Content -->
                        <h3 class="text-lg font-bold text-gray-900 mb-2">
                            {{ $item['title'] }}
                        </h3>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            {{ $item['description'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Solution intro with arrow -->
        <div class="text-center animate-on-scroll">
            <div class="inline-flex flex-col items-center">
                <!-- Arrow down -->
                <svg class="w-8 h-12 text-blue-500 animate-bounce mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>

                <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-full text-lg font-bold shadow-lg shadow-blue-500/30">
                    {{ $translations['problem']['solution_intro'] }}
                </div>
            </div>
        </div>
    </div>
</section>
