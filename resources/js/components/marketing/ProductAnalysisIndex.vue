<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Mahsulot Analizi</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mahsulot va xizmatlaringizni marketing nuqtai nazaridan tahlil qiling</p>
      </div>
      <button
        @click="showAddModal = true"
        class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-sm"
      >
        <PlusIcon class="w-4 h-4 mr-2" />
        Mahsulot qo'shish
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in statCards" :key="stat.label" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center gap-3">
          <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', stat.bg]">
            <component :is="stat.icon" :class="['w-5 h-5', stat.color]" />
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stat.value }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Mahsulotlar ro'yxati</h3>
        <div class="relative">
          <MagnifyingGlassIcon class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input v-model="search" type="text" placeholder="Qidirish..." class="pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg w-56 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50/80 dark:bg-gray-900/30">
              <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Mahsulot</th>
              <th class="text-left text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Kategoriya</th>
              <th class="text-right text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Narx</th>
              <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">USP bali</th>
              <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Raqobat</th>
              <th class="text-center text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider px-5 py-3">Marketing holati</th>
              <th class="w-16"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
            <tr v-for="product in filteredProducts" :key="product.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20 transition-colors">
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div :class="['w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold text-white', getProductColor(product)]">
                    {{ product.name?.charAt(0) }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ product.name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ product.short_desc }}</p>
                  </div>
                </div>
              </td>
              <td class="px-5 py-4">
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                  {{ product.category }}
                </span>
              </td>
              <td class="px-5 py-4 text-right">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(product.price) }}</span>
              </td>
              <td class="px-5 py-4 text-center">
                <div class="flex items-center justify-center gap-1">
                  <div class="w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full', getScoreColor(product.usp_score)]" :style="{ width: product.usp_score + '%' }"></div>
                  </div>
                  <span class="text-xs font-semibold" :class="getScoreTextColor(product.usp_score)">{{ product.usp_score }}%</span>
                </div>
              </td>
              <td class="px-5 py-4 text-center">
                <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-md', getCompetitionClass(product.competition)]">
                  {{ getCompetitionLabel(product.competition) }}
                </span>
              </td>
              <td class="px-5 py-4 text-center">
                <span :class="['inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-lg', getStatusClass(product.marketing_status)]">
                  <span :class="['w-1.5 h-1.5 rounded-full', getStatusDotClass(product.marketing_status)]"></span>
                  {{ getStatusLabel(product.marketing_status) }}
                </span>
              </td>
              <td class="px-3 py-4">
                <Link :href="getHref('/product-analysis/' + product.id)" class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                  <ChevronRightIcon class="w-4 h-4" />
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="filteredProducts.length === 0" class="py-16 text-center">
        <CubeIcon class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" />
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Mahsulotlar topilmadi</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Mahsulotlaringizni qo'shib, marketing tahlilini boshlang</p>
        <button @click="showAddModal = true" class="inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 transition-colors">
          <PlusIcon class="w-4 h-4 mr-1.5" />
          Birinchi mahsulotni qo'shish
        </button>
      </div>
    </div>

    <!-- Tezkor Tavsiyalar (Insights) -->
    <div v-if="insights.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
        <LightBulbIcon class="w-5 h-5 text-amber-500" />
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tezkor tavsiyalar</h3>
        <span class="ml-auto text-xs text-gray-400">{{ insights.length }} ta</span>
      </div>
      <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div v-for="insight in insights" :key="insight.id" :class="[
          'p-3 rounded-lg border-l-4',
          insight.priority === 'high' ? 'bg-red-50 dark:bg-red-900/10 border-red-500' :
          insight.priority === 'medium' ? 'bg-amber-50 dark:bg-amber-900/10 border-amber-500' :
          'bg-blue-50 dark:bg-blue-900/10 border-blue-500'
        ]">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ insight.title }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">{{ insight.description }}</p>
              <div class="flex items-center gap-2 mt-2">
                <span v-if="insight.product_name" class="text-[10px] text-gray-400">{{ insight.product_name }}</span>
                <Link v-if="insight.product_id" :href="getHref('/product-analysis/' + insight.product_id)" class="text-[10px] text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
                  {{ insight.action_text || "Ko'rish" }}
                </Link>
              </div>
            </div>
            <span :class="[
              'px-1.5 py-0.5 text-[9px] font-bold rounded uppercase flex-shrink-0',
              insight.priority === 'high' ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' :
              insight.priority === 'medium' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' :
              'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'
            ]">{{ insight.priority === 'high' ? 'Muhim' : insight.priority === 'medium' ? "O'rta" : 'Past' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Analytics -->
    <div v-if="products.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-2 mb-4">
          <ChartBarIcon class="w-5 h-5 text-indigo-500" />
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Mahsulot sog'ligi</h3>
        </div>
        <div class="space-y-3">
          <div v-for="product in products.slice(0, 5)" :key="'h-' + product.id" class="flex items-center gap-3">
            <Link :href="getHref('/product-analysis/' + product.id)" class="flex items-center gap-2 min-w-0 flex-1 hover:opacity-80 transition-opacity">
              <div :class="['w-7 h-7 rounded-md flex items-center justify-center text-xs font-bold text-white flex-shrink-0', getProductColor(product)]">{{ product.name?.charAt(0) }}</div>
              <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ product.name }}</span>
            </Link>
            <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden flex-shrink-0">
              <div :class="['h-full rounded-full', product.health_score >= 70 ? 'bg-emerald-500' : product.health_score >= 40 ? 'bg-amber-500' : 'bg-red-500']" :style="{ width: (product.health_score || product.usp_score) + '%' }"></div>
            </div>
            <span class="text-xs font-semibold w-8 text-right" :class="(product.health_score || product.usp_score) >= 70 ? 'text-emerald-600' : (product.health_score || product.usp_score) >= 40 ? 'text-amber-600' : 'text-red-600'">
              {{ product.health_score || product.usp_score }}%
            </span>
          </div>
        </div>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
        <div class="flex items-center gap-2 mb-4">
          <TagIcon class="w-5 h-5 text-purple-500" />
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Narx va margin</h3>
        </div>
        <div class="space-y-3">
          <div v-for="product in products.slice(0, 5)" :key="'m-' + product.id" class="flex items-center justify-between">
            <Link :href="getHref('/product-analysis/' + product.id)" class="text-sm font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate">{{ product.name }}</Link>
            <div class="flex items-center gap-3 flex-shrink-0">
              <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(product.price) }}</span>
              <span v-if="product.margin_percent" :class="['text-xs font-bold px-1.5 py-0.5 rounded', product.margin_percent >= 30 ? 'bg-emerald-50 text-emerald-700' : product.margin_percent >= 15 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700']">
                {{ product.margin_percent }}%
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Product Modal -->
    <Teleport to="body">
      <Transition enter-active-class="transition-opacity duration-150" leave-active-class="transition-opacity duration-100" enter-from-class="opacity-0" leave-to-class="opacity-0">
        <div v-if="showAddModal" @click="showAddModal = false" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
          <Transition enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-100" enter-from-class="opacity-0 scale-95" leave-to-class="opacity-0 scale-95">
            <div v-if="showAddModal" @click.stop class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div class="px-6 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                <h3 class="text-lg font-bold">Yangi mahsulot qo'shish</h3>
                <p class="text-sm text-indigo-200 mt-0.5">Marketing tahlili uchun mahsulot ma'lumotlarini kiriting</p>
              </div>

              <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Mahsulot nomi *</label>
                  <input v-model="form.name" type="text" class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="Masalan: Premium Kurs" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tavsif</label>
                  <textarea v-model="form.short_desc" rows="3" class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="Mahsulot yoki xizmat haqida batafsil yozing..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategoriya</label>
                    <select v-model="form.category" class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl">
                      <option value="product">Mahsulot</option>
                      <option value="service">Xizmat</option>
                      <option value="course">Kurs</option>
                      <option value="subscription">Obuna</option>
                      <option value="other">Boshqa</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Narx (so'm)</label>
                    <input :value="formatInputPrice(form.price)" @input="form.price = parseInputPrice($event.target.value)" type="text" inputmode="numeric" class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="0" />
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tannarx (so'm)</label>
                  <input :value="formatInputPrice(form.cost)" @input="form.cost = parseInputPrice($event.target.value)" type="text" inputmode="numeric" class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500" placeholder="Ixtiyoriy — margin hisoblash uchun" />
                </div>

                <!-- Afzalliklar — dinamik ro'yxat -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Afzalliklar</label>
                  <div class="space-y-2">
                    <div v-for="(item, i) in advantageItems" :key="'a'+i" class="flex items-center gap-2">
                      <div class="w-6 h-6 bg-emerald-100 dark:bg-emerald-900/30 rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                      </div>
                      <input v-model="advantageItems[i]" type="text" class="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" :placeholder="'Afzallik ' + (i+1)" />
                      <button v-if="advantageItems.length > 1" @click="advantageItems.splice(i, 1)" class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                      </button>
                    </div>
                    <button @click="advantageItems.push('')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                      Afzallik qo'shish
                    </button>
                  </div>
                </div>

                <!-- Kamchiliklar — dinamik ro'yxat -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Kamchiliklar</label>
                  <div class="space-y-2">
                    <div v-for="(item, i) in weaknessItems" :key="'w'+i" class="flex items-center gap-2">
                      <div class="w-6 h-6 bg-red-100 dark:bg-red-900/30 rounded-md flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                      </div>
                      <input v-model="weaknessItems[i]" type="text" class="flex-1 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500" :placeholder="'Kamchilik ' + (i+1)" />
                      <button v-if="weaknessItems.length > 1" @click="weaknessItems.splice(i, 1)" class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                      </button>
                    </div>
                    <button @click="weaknessItems.push('')" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                      Kamchilik qo'shish
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Maqsadli auditoriya</label>
                  <div v-if="dreamBuyers.length > 0" class="space-y-2">
                    <label
                      v-for="db in dreamBuyers"
                      :key="db.id"
                      :class="[
                        'flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all',
                        form.target_audience === db.name
                          ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-900/20'
                          : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                      ]"
                    >
                      <input type="radio" :value="db.name" v-model="form.target_audience" class="sr-only" />
                      <div :class="[
                        'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0',
                        form.target_audience === db.name ? 'bg-indigo-500' : 'bg-gray-200 dark:bg-gray-700'
                      ]">
                        <svg v-if="form.target_audience === db.name" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        <svg v-else class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                      </div>
                      <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                          {{ db.name }}
                          <span v-if="db.is_primary" class="px-1.5 py-0.5 text-[9px] font-bold bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded">Asosiy</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ db.description || db.occupation || db.age_range || '' }}</p>
                      </div>
                    </label>
                  </div>
                  <div v-else class="p-3 bg-gray-50 dark:bg-gray-900/30 rounded-xl text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Avval "Mijoz Portreti" bo'limida ideal mijozni yarating</p>
                    <Link :href="getHref('/dream-buyer')" class="text-xs text-indigo-600 dark:text-indigo-400 font-medium hover:underline mt-1 inline-block">Mijoz portretini yaratish</Link>
                  </div>
                </div>
              </div>

              <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex gap-3">
                <button @click="showAddModal = false" class="flex-1 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 transition-colors">Bekor qilish</button>
                <button @click="saveProduct" :disabled="!form.name || isSaving" class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50 shadow-sm">
                  <span v-if="isSaving">Saqlanmoqda...</span>
                  <span v-else>Saqlash</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
  PlusIcon, MagnifyingGlassIcon, CubeIcon, ChevronRightIcon,
  LightBulbIcon, ChartBarIcon, TagIcon, SparklesIcon,
  ShoppingBagIcon, UserGroupIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  panelType: { type: String, required: true, validator: (v) => ['business', 'marketing'].includes(v) },
  products: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
  insights: { type: Array, default: () => [] },
  dreamBuyers: { type: Array, default: () => [] },
});

const search = ref('');
const showAddModal = ref(false);
const isSaving = ref(false);
const form = ref({ name: '', short_desc: '', category: 'product', price: '', cost: '', target_audience: '' });
const advantageItems = ref(['']);
const weaknessItems = ref(['']);

const getHref = (path) => {
  const prefix = props.panelType === 'business' ? '/business' : '/marketing';
  return prefix + path;
};

const categoryLabels = { product: 'Mahsulot', service: 'Xizmat', course: 'Kurs', subscription: 'Obuna', other: 'Boshqa' };

const statCards = computed(() => [
  { value: props.stats.total || props.products.length, label: 'Jami mahsulotlar', icon: CubeIcon, bg: 'bg-indigo-50 dark:bg-indigo-900/30', color: 'text-indigo-600 dark:text-indigo-400' },
  { value: props.stats.active_marketing || 0, label: 'Aktiv marketing', icon: SparklesIcon, bg: 'bg-emerald-50 dark:bg-emerald-900/30', color: 'text-emerald-600 dark:text-emerald-400' },
  { value: props.stats.avg_usp || 0, label: "O'rtacha USP bal", icon: LightBulbIcon, bg: 'bg-amber-50 dark:bg-amber-900/30', color: 'text-amber-600 dark:text-amber-400' },
  { value: props.stats.needs_attention || 0, label: "E'tibor kerak", icon: TagIcon, bg: 'bg-red-50 dark:bg-red-900/30', color: 'text-red-600 dark:text-red-400' },
]);

const filteredProducts = computed(() => {
  if (!search.value) return props.products;
  const q = search.value.toLowerCase();
  return props.products.filter(p => p.name?.toLowerCase().includes(q) || p.category?.toLowerCase().includes(q));
});

const colors = ['bg-indigo-600', 'bg-purple-600', 'bg-blue-600', 'bg-emerald-600', 'bg-amber-600', 'bg-rose-600', 'bg-cyan-600'];
const getProductColor = (product) => colors[(product.name?.charCodeAt(0) || 0) % colors.length];

const formatPrice = (price) => price ? new Intl.NumberFormat('uz-UZ').format(price) + " so'm" : "—";

// Input uchun minglik formatlash (1000000 → 1 000 000)
const formatInputPrice = (value) => {
  if (!value && value !== 0) return '';
  return new Intl.NumberFormat('uz-UZ').format(Number(value) || 0);
};
const parseInputPrice = (formatted) => {
  return Number(String(formatted).replace(/\s/g, '').replace(/[^\d]/g, '')) || '';
};

const getScoreColor = (score) => score >= 70 ? 'bg-emerald-500' : score >= 40 ? 'bg-amber-500' : 'bg-red-500';
const getScoreTextColor = (score) => score >= 70 ? 'text-emerald-600 dark:text-emerald-400' : score >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400';

const getCompetitionClass = (level) => ({ low: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400', medium: 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400', high: 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400' })[level] || 'bg-gray-100 text-gray-600';
const getCompetitionLabel = (level) => ({ low: 'Past', medium: "O'rta", high: 'Yuqori' })[level] || '—';

const getStatusClass = (status) => ({ active: 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50', planned: 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border border-blue-200/50', paused: 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border border-gray-200/50', none: 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200/50' })[status] || 'bg-gray-100 text-gray-600';
const getStatusDotClass = (status) => ({ active: 'bg-emerald-500', planned: 'bg-blue-500', paused: 'bg-gray-400', none: 'bg-red-500' })[status] || 'bg-gray-400';
const getStatusLabel = (status) => ({ active: 'Aktiv', planned: 'Rejalashtirilgan', paused: "To'xtatilgan", none: 'Yo\'q' })[status] || '—';

const getPricePosition = (product) => {
  if (!product.price || !product.market_avg_price) return 50;
  return Math.min(100, Math.max(5, (product.price / (product.market_avg_price * 2)) * 100));
};
const getPriceLabel = (product) => {
  if (!product.price || !product.market_avg_price) return "Ma'lumot yo'q";
  const ratio = product.price / product.market_avg_price;
  if (ratio < 0.8) return 'Arzon';
  if (ratio > 1.2) return 'Qimmat';
  return "O'rtacha";
};

const saveProduct = () => {
  if (!form.value.name) return;
  isSaving.value = true;
  const payload = {
    ...form.value,
    advantages: advantageItems.value.filter(Boolean).join('\n'),
    weaknesses: weaknessItems.value.filter(Boolean).join('\n'),
  };
  router.post(getHref('/product-analysis'), payload, {
    preserveScroll: true,
    onSuccess: () => {
      showAddModal.value = false;
      form.value = { name: '', short_desc: '', category: 'product', price: '', cost: '', target_audience: '' };
      advantageItems.value = [''];
      weaknessItems.value = [''];
      isSaving.value = false;
    },
    onError: () => { isSaving.value = false; },
  });
};
</script>
