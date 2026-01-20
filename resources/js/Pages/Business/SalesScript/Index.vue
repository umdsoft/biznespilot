<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import BusinessLayout from '@/layouts/BusinessLayout.vue';
import {
    ClipboardDocumentIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon as CheckCircleSolidIcon } from '@heroicons/vue/24/solid';

defineProps({
    currentBusiness: Object,
});

// Toast
const toast = ref({ show: false, message: '', type: 'success' });
const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 2500);
};

// Copy script
const copyScript = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        showToast('Skript nusxalandi!');
    } catch {
        showToast('Xatolik yuz berdi', 'error');
    }
};

// Section collapse states
const collapsedSections = ref({});
const toggleSection = (sectionId) => {
    collapsedSections.value[sectionId] = !collapsedSections.value[sectionId];
};

const expandAll = () => {
    collapsedSections.value = {};
};

const collapseAll = () => {
    collapsedSections.value = {
        'section-1': true,
        'section-2': true,
        'section-3': true,
        'section-4': true,
        'section-5': true,
        'section-6': true,
        'section-faq': true,
    };
};

const scrollToSection = (sectionId) => {
    collapsedSections.value[sectionId] = false;
    document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
</script>

<template>
    <BusinessLayout title="Sotuv Skriptlari">
        <Head title="Sotuv Skriptlari" />

        <div class="sales-arsenal">
            <!-- Toast -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-2"
            >
                <div v-if="toast.show" class="fixed bottom-6 right-6 z-50">
                    <div :class="[
                        'px-4 py-3 rounded-xl shadow-lg flex items-center gap-2',
                        toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                    ]">
                        <CheckCircleSolidIcon v-if="toast.type === 'success'" class="w-5 h-5" />
                        {{ toast.message }}
                    </div>
                </div>
            </Transition>

            <!-- HEADER -->
            <div class="header">
                <div class="header-badge">⚡ ULTIMATE EDITION</div>
                <h1>SOTUV ARSENALI</h1>
                <p>6 bosqichli to'liq sotuv skripti va psixologik texnikalar</p>
                <div class="header-stats">
                    <div class="stat-card">
                        <div class="stat-value">6</div>
                        <div class="stat-label">Bosqich</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">15+</div>
                        <div class="stat-label">E'tiroz javoblari</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">12</div>
                        <div class="stat-label">Yopish usuli</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">10</div>
                        <div class="stat-label">FAQ</div>
                    </div>
                </div>
            </div>

            <!-- NAVIGATION -->
            <nav class="nav">
                <button class="nav-btn gold" @click="expandAll">📂 Hammasini ochish</button>
                <button class="nav-btn red" @click="collapseAll">📁 Yopish</button>
                <button class="nav-btn outline" @click="scrollToSection('section-1')">1️⃣ Aloqa</button>
                <button class="nav-btn outline" @click="scrollToSection('section-2')">2️⃣ SPIN</button>
                <button class="nav-btn outline" @click="scrollToSection('section-3')">3️⃣ Taqdimot</button>
                <button class="nav-btn outline" @click="scrollToSection('section-4')">4️⃣ E'tirozlar</button>
                <button class="nav-btn outline" @click="scrollToSection('section-5')">5️⃣ Yopish</button>
                <button class="nav-btn outline" @click="scrollToSection('section-6')">6️⃣ Follow-up</button>
                <button class="nav-btn outline" @click="scrollToSection('section-faq')">❓ FAQ</button>
            </nav>

            <!-- ==================== BOSQICH 1: ALOQA O'RNATISH ==================== -->
            <section class="section" id="section-1" :class="{ collapsed: collapsedSections['section-1'] }">
                <div class="section-header" @click="toggleSection('section-1')">
                    <div class="section-icon blue">📞</div>
                    <div class="section-info">
                        <div class="section-title">1-BOSQICH: ALOQA O'RNATISH</div>
                        <div class="section-subtitle">Birinchi taassurot yaratish (15-30 sek)</div>
                    </div>
                    <div class="section-badges">
                        <span class="section-badge gold">LIKING</span>
                        <span class="section-badge new">PATTERN INTERRUPT</span>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <div class="flow">
                        <div class="flow-node start">📞 QO'NG'IROQ BOSHLANDI</div>

                        <div class="flow-arrow">
                            <div class="flow-arrow-line"></div>
                            <div>▼</div>
                        </div>

                        <div class="flow-node script">
                            <span class="label">LIKING + PATTERN INTERRUPT</span>
                            <div class="script-content">
                                <div class="script-text">
                                    "Assalomu alaykum, <span class="highlight">[ISM]</span>!<br><br>
                                    Men <span class="highlight">[Ismingiz]</span>, <span class="highlight">[Kompaniya]</span>dan.<br>
                                    <strong>2 daqiqa</strong> — va siz <span class="highlight">[FOYDA]</span> haqida bilasiz.<br><br>
                                    Hozir gaplashsak bo'ladimi?"
                                </div>
                                <button @click="copyScript('Assalomu alaykum, [ISM]! Men [Ismingiz], [Kompaniya]dan. 2 daqiqa — va siz [FOYDA] haqida bilasiz. Hozir gaplashsak bo\'ladimi?')" class="copy-btn">
                                    <ClipboardDocumentIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <div class="flow-arrow-line"></div>
                            <div>▼</div>
                        </div>

                        <div class="flow-node decision">🤔 MIJOZ JAVOBI?</div>

                        <div class="flow-branches">
                            <div class="flow-branch">
                                <div class="branch-label">✅ "Ha"</div>
                                <div class="branch-response positive">"Ha, gapiring"</div>
                                <div class="branch-action">"Ajoyib, rahmat!"</div>
                                <div class="branch-goto success">➡️ 2-BOSQICHga</div>
                            </div>

                            <div class="flow-branch">
                                <div class="branch-label">⏰ "Band"</div>
                                <div class="branch-response neutral">"Hozir bandman"</div>
                                <div class="branch-action"><strong>SCARCITY:</strong><br>"Bugun kechqurun yoki ertaga — qaysi biri?"</div>
                                <div class="branch-goto">📅 Vaqt belgilash</div>
                            </div>

                            <div class="flow-branch">
                                <div class="branch-label">❌ "Yo'q"</div>
                                <div class="branch-response danger">"Kerak emas"</div>
                                <div class="branch-action"><strong>PATTERN INTERRUPT:</strong><br>"Ajoyib! <strong>Faqat 1 savol:</strong> [MUAMMO] sizda ham bormi?"</div>
                                <div class="branch-goto loop">🔄 Qayta urinish</div>
                            </div>

                            <div class="flow-branch">
                                <div class="branch-label">🤨 "Nima?"</div>
                                <div class="branch-response neutral">"Nima haqida?"</div>
                                <div class="branch-action"><strong>LOSS AVERSION:</strong><br>"[SOHA]da oyiga [X] yo'qotmaslik haqida."</div>
                                <div class="branch-goto">⏳ Javob kutish</div>
                            </div>
                        </div>
                    </div>

                    <div class="key-point">
                        <h4>💡 1-BOSQICH TEXNIKALARI</h4>
                        <p>
                            <strong>LIKING:</strong> Ismini ayting, iliq ohang<br>
                            <strong>PATTERN INTERRUPT:</strong> "Kerak emas" → "Faqat 1 savol..."<br>
                            <strong>SCARCITY:</strong> Alternativ vaqt taklifi<br>
                            <strong>LOSS AVERSION:</strong> Yo'qotish haqida gapirish
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==================== BOSQICH 2: SPIN SAVOLLAR ==================== -->
            <section class="section" id="section-2" :class="{ collapsed: collapsedSections['section-2'] }">
                <div class="section-header" @click="toggleSection('section-2')">
                    <div class="section-icon purple">🎯</div>
                    <div class="section-info">
                        <div class="section-title">2-BOSQICH: EHTIYOJNI ANIQLASH — SPIN</div>
                        <div class="section-subtitle">SPIN savollar bilan ehtiyojni toping (2-5 daq)</div>
                    </div>
                    <div class="section-badges">
                        <span class="section-badge gold">SPIN</span>
                        <span class="section-badge pro">MIRRORING</span>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <div class="flow">
                        <div class="flow-node script situation">
                            <span class="label">S — SITUATION</span>
                            <div class="script-content">
                                <div class="script-text">"Hozirda <span class="highlight">[SOHA]</span> bo'yicha qanday yechimlardan foydalanasiz?"</div>
                                <button @click="copyScript('Hozirda [SOHA] bo\'yicha qanday yechimlardan foydalanasiz?')" class="copy-btn">
                                    <ClipboardDocumentIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <div class="flow-arrow-line"></div>
                            <div>▼</div>
                        </div>

                        <div class="flow-node script problem">
                            <span class="label">P — PROBLEM</span>
                            <div class="script-content">
                                <div class="script-text">
                                    "Bu jarayonda <strong>eng katta qiyinchilik</strong> nima?<br>
                                    <strong>Qancha vaqt/pul</strong> ketadi bunga?"<br><br>
                                    ⚠️ <strong>OG'RIQ NUQTASINI YOZIB OLING!</strong>
                                </div>
                                <button @click="copyScript('Bu jarayonda eng katta qiyinchilik nima? Qancha vaqt/pul ketadi bunga?')" class="copy-btn">
                                    <ClipboardDocumentIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <div class="flow-arrow-line"></div>
                            <div>▼</div>
                        </div>

                        <div class="flow-node script implication">
                            <span class="label">I — IMPLICATION</span>
                            <div class="script-content">
                                <div class="script-text">
                                    "Bu muammo tufayli oyiga <strong>qancha yo'qotyapsiz</strong>?<br>
                                    6 oy kutish <strong>qancha zarar</strong> keltiradi?"
                                </div>
                                <button @click="copyScript('Bu muammo tufayli oyiga qancha yo\'qotyapsiz? 6 oy kutish qancha zarar keltiradi?')" class="copy-btn">
                                    <ClipboardDocumentIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <div class="flow-arrow-line"></div>
                            <div>▼</div>
                        </div>

                        <div class="flow-node script need-payoff">
                            <span class="label dark">N — NEED-PAYOFF</span>
                            <div class="script-content">
                                <div class="script-text">
                                    "Agar bu muammo <strong>to'liq hal bo'lsa</strong>, sizga nima o'zgaradi?<br>
                                    <strong>Tasavvur qiling:</strong> 3 oydan keyin..."
                                </div>
                                <button @click="copyScript('Agar bu muammo to\'liq hal bo\'lsa, sizga nima o\'zgaradi? Tasavvur qiling: 3 oydan keyin...')" class="copy-btn dark">
                                    <ClipboardDocumentIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="key-point">
                        <h4>💡 OLTIN QOIDA: 70% TINGLANG, 30% GAPIRING</h4>
                        <p>
                            <strong>MIRRORING:</strong> Mijoz so'zlarini takrorlang<br>
                            <strong>LOSS AVERSION:</strong> Yo'qotishni aniq raqamlarda ayting<br>
                            <strong>FUTURE PACING:</strong> Yaxshi kelajakni tasvirlang<br>
                            <strong>Mavzudan chiqsa:</strong> "Qiziq! Aytgancha, [SAVOL]ga qaytaylik..."
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==================== BOSQICH 3: TAQDIMOT ==================== -->
            <section class="section" id="section-3" :class="{ collapsed: collapsedSections['section-3'] }">
                <div class="section-header" @click="toggleSection('section-3')">
                    <div class="section-icon orange">✨</div>
                    <div class="section-info">
                        <div class="section-title">3-BOSQICH: TAQDIMOT — FAB</div>
                        <div class="section-subtitle">Feature-Advantage-Benefit formulasi (3-5 daq)</div>
                    </div>
                    <div class="section-badges">
                        <span class="section-badge gold">FAB</span>
                        <span class="section-badge new">STORYTELLING</span>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <div class="flow">
                        <div class="flow-node script presentation">
                            <span class="label">FAB + STORYTELLING + SOCIAL PROOF</span>
                            <div class="script-content">
                                <div class="script-text">
                                    "<strong>Siz aytdingiz:</strong> <span class="highlight">[MIJOZ AYTGAN MUAMMO]</span>...<br><br>
                                    Aynan shuning uchun bizda <strong><span class="highlight">[YECHIM]</span></strong> bor.<br>
                                    Bu sizga <strong><span class="highlight">[AFZALLIK]</span></strong> beradi.<br>
                                    Natijada <strong><span class="highlight">[RAQAMLI FOYDA]</span></strong> olasiz.<br><br>
                                    <strong>HIKOYA:</strong> <span class="highlight">[Kompaniya X]</span> ham xuddi shunday edi.<br>
                                    Ular biz bilan 3 oyda <span class="highlight">[NATIJA]</span>ga erishdi.<br><br>
                                    <strong>RAQAMLAR:</strong> 500+ kompaniya bizni tanlagan.<br><br>
                                    Bu sizga mos keladi, <strong>to'g'rimi?</strong>"
                                </div>
                                <button @click="copyScript('Siz aytdingiz: [MIJOZ AYTGAN MUAMMO]... Aynan shuning uchun bizda [YECHIM] bor. Bu sizga [AFZALLIK] beradi. Natijada [RAQAMLI FOYDA] olasiz.')" class="copy-btn">
                                    <ClipboardDocumentIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="flow-arrow">
                            <div class="flow-arrow-line"></div>
                            <div>▼</div>
                        </div>

                        <div class="flow-node decision">🤔 MIJOZ REAKSIYASI?</div>

                        <div class="flow-branches">
                            <div class="flow-branch">
                                <div class="branch-label">🤩 "Qiziq!"</div>
                                <div class="branch-response positive">"Qiziq! Batafsilroq"</div>
                                <div class="branch-action"><strong>ASSUMPTIVE:</strong><br>"Qachon boshlaymiz — dushanba yoki chorshanba?"</div>
                                <div class="branch-goto success">➡️ 5-BOSQICHga</div>
                            </div>

                            <div class="flow-branch">
                                <div class="branch-label">🤔 E'tiroz</div>
                                <div class="branch-response danger">"Qimmat" / "O'ylash kerak"</div>
                                <div class="branch-action">E'tiroz paydo bo'ldi</div>
                                <div class="branch-goto">➡️ 4-BOSQICHga</div>
                            </div>

                            <div class="flow-branch">
                                <div class="branch-label">😐 Noaniq</div>
                                <div class="branch-response neutral">"Bilmadim..."</div>
                                <div class="branch-action"><strong>TIE-DOWN:</strong><br>"Qaysi qismi noaniq?"</div>
                                <div class="branch-goto loop">🔄 Qayta tushuntirish</div>
                            </div>
                        </div>
                    </div>

                    <div class="key-point">
                        <h4>💡 MUHIM QOIDA</h4>
                        <p>Doimo <strong>"Siz aytdingiz..."</strong> bilan boshlang! Mijozning o'z so'zlarini ishlating — bu ishonch yaratadi va e'tirozlarni kamaytiradi.</p>
                    </div>
                </div>
            </section>

            <!-- ==================== BOSQICH 4: E'TIROZLAR ==================== -->
            <section class="section" id="section-4" :class="{ collapsed: collapsedSections['section-4'] }">
                <div class="section-header" @click="toggleSection('section-4')">
                    <div class="section-icon red">🛡️</div>
                    <div class="section-info">
                        <div class="section-title">4-BOSQICH: E'TIROZLARNI BARTARAF ETISH</div>
                        <div class="section-subtitle">LAER texnikasi bilan 15+ e'tirozga javob</div>
                    </div>
                    <div class="section-badges">
                        <span class="section-badge gold">LAER</span>
                        <span class="section-badge pro">15+ javob</span>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <!-- LAER Method -->
                    <div class="method-card">
                        <div class="method-header">
                            <div class="method-icon">🧠</div>
                            <div class="method-name">LAER TEXNIKASI</div>
                            <div class="method-origin">Universal e'tiroz javob tizimi</div>
                        </div>
                        <div class="method-body">
                            <div class="method-steps">
                                <div class="method-step">
                                    <div class="step-letter">L</div>
                                    <div class="step-content">
                                        <div class="step-title">LISTEN</div>
                                        <div class="step-desc">Tinglang, bo'lmang!</div>
                                    </div>
                                </div>
                                <div class="method-step">
                                    <div class="step-letter">A</div>
                                    <div class="step-content">
                                        <div class="step-title">ACKNOWLEDGE</div>
                                        <div class="step-desc">"Tushundim, muhim savol"</div>
                                    </div>
                                </div>
                                <div class="method-step">
                                    <div class="step-letter">E</div>
                                    <div class="step-content">
                                        <div class="step-title">EXPLORE</div>
                                        <div class="step-desc">"Aniqroq aytsangiz?"</div>
                                    </div>
                                </div>
                                <div class="method-step">
                                    <div class="step-letter">R</div>
                                    <div class="step-content">
                                        <div class="step-title">RESPOND</div>
                                        <div class="step-desc">Javob bering va QAYTARING</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Objections Grid -->
                    <div class="objections-grid">
                        <!-- QIMMAT -->
                        <div class="objection-card">
                            <div class="objection-header red">💰 "QIMMAT"</div>
                            <div class="objection-body">
                                <div class="objection-tags">
                                    <span class="tag">REFRAMING</span>
                                    <span class="tag">ANCHORING</span>
                                    <span class="tag">LOSS AVERSION</span>
                                </div>
                                <div class="objection-script">
                                    <strong>FEEL-FELT-FOUND:</strong><br><br>
                                    Tushundim, siz qimmat deb <span class="highlight">his qilyapsiz</span>.<br>
                                    Ko'pchilik ham avval <span class="highlight">shunday his qilgan</span>.<br><br>
                                    Lekin <span class="highlight">aniqlab olishdi</span>:<br>
                                    • Hozir bu muammoga oyiga <strong>[X]</strong> sarflaysiz<br>
                                    • Biz bilan <strong>[Y]</strong> tejaysiz<br>
                                    • <strong>3 oyda investitsiya qaytadi</strong><br><br>
                                    Bu xarajat emas — <strong>investitsiya</strong>. Mantiqiymi?
                                </div>
                                <button @click="copyScript('Tushundim, siz qimmat deb his qilyapsiz. Ko\'pchilik ham avval shunday his qilgan. Lekin aniqlab olishdi: Hozir bu muammoga oyiga [X] sarflaysiz. Biz bilan [Y] tejaysiz. 3 oyda investitsiya qaytadi. Bu xarajat emas — investitsiya. Mantiqiymi?')" class="objection-copy">
                                    <ClipboardDocumentIcon class="w-4 h-4" /> Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- O'YLAB KO'RAMAN -->
                        <div class="objection-card">
                            <div class="objection-header orange">⏳ "O'YLAB KO'RAMAN"</div>
                            <div class="objection-body">
                                <div class="objection-tags">
                                    <span class="tag">FOMO</span>
                                    <span class="tag">SCARCITY</span>
                                    <span class="tag">SCALE CLOSE</span>
                                </div>
                                <div class="objection-script">
                                    "Albatta, bu muhim qaror.<br><br>
                                    <strong>ANIQ nima</strong> ustida o'ylaysiz?<br>
                                    • Narxmi? → [NARX javob]<br>
                                    • Sifatmi? → [SIFAT javob]<br>
                                    • Boshqa variantmi? → [TAQQOSLASH]<br><br>
                                    <span class="highlight">1 dan 10 gacha</span> — qanchalik tayyorsiz?<br>
                                    <strong>Nima 10 ga yetkazadi?</strong>"
                                </div>
                                <button @click="copyScript('Albatta, bu muhim qaror. ANIQ nima ustida o\'ylaysiz? 1 dan 10 gacha — qanchalik tayyorsiz? Nima 10 ga yetkazadi?')" class="objection-copy">
                                    <ClipboardDocumentIcon class="w-4 h-4" /> Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- VAQT YO'Q -->
                        <div class="objection-card">
                            <div class="objection-header purple">⏰ "VAQT YO'Q"</div>
                            <div class="objection-body">
                                <div class="objection-tags">
                                    <span class="tag">REFRAMING</span>
                                    <span class="tag">FUTURE PACING</span>
                                </div>
                                <div class="objection-script">
                                    "Tushundim, vaqtingiz qimmat.<br><br>
                                    <span class="highlight">Aynan shuning uchun</span> bu yechim —<br>
                                    sizga <strong>oyiga 20 soat</strong> tejaydi.<br><br>
                                    <strong>60 sekundda</strong> asosiy fikrni aytsam?"
                                </div>
                                <button @click="copyScript('Tushundim, vaqtingiz qimmat. Aynan shuning uchun bu yechim — sizga oyiga 20 soat tejaydi. 60 sekundda asosiy fikrni aytsam?')" class="objection-copy">
                                    <ClipboardDocumentIcon class="w-4 h-4" /> Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- BOSHQASI BOR -->
                        <div class="objection-card">
                            <div class="objection-header blue">🏢 "BOSHQASI BOR"</div>
                            <div class="objection-body">
                                <div class="objection-tags">
                                    <span class="tag">SOCIAL PROOF</span>
                                    <span class="tag">STORYTELLING</span>
                                </div>
                                <div class="objection-script">
                                    "Ajoyib! Demak bu soha sizga muhim.<br><br>
                                    <strong>Nima yoqadi</strong> ularda?<br>
                                    <strong>Nima yaxshilanishi</strong> mumkin edi?<br><br>
                                    <span class="highlight">[Kompaniya Y]</span> ham [raqobatchi] bilan ishlardi.<br>
                                    Biz bilan o'tgandan keyin <strong>[NATIJA]</strong>.<br><br>
                                    <strong>Bepul sinov</strong> taklif qilaman — taqqoslang."
                                </div>
                                <button @click="copyScript('Ajoyib! Demak bu soha sizga muhim. Nima yoqadi ularda? Nima yaxshilanishi mumkin edi? Bepul sinov taklif qilaman — taqqoslang.')" class="objection-copy">
                                    <ClipboardDocumentIcon class="w-4 h-4" /> Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- MEN QAROR QILMAYMAN -->
                        <div class="objection-card">
                            <div class="objection-header green">🤝 "MEN QAROR QILMAYMAN"</div>
                            <div class="objection-body">
                                <div class="objection-tags">
                                    <span class="tag">COMMITMENT</span>
                                    <span class="tag">CHAMPION</span>
                                </div>
                                <div class="objection-script">
                                    "Tushundim. Lekin <strong>sizning fikringiz</strong> ham muhim.<br><br>
                                    Siz shaxsan nima deb o'ylaysiz?<br><br>
                                    Keling, <strong>qaror qiluvchi bilan birgalikda</strong> uchrashuvni belgilaylik.<br><br>
                                    Men sizga <strong>tayyor prezentatsiya</strong> tayyorlayman."
                                </div>
                                <button @click="copyScript('Tushundim. Lekin sizning fikringiz ham muhim. Siz shaxsan nima deb o\'ylaysiz? Keling, qaror qiluvchi bilan birgalikda uchrashuvni belgilaylik.')" class="objection-copy">
                                    <ClipboardDocumentIcon class="w-4 h-4" /> Nusxalash
                                </button>
                            </div>
                        </div>

                        <!-- BYUDJET YO'Q -->
                        <div class="objection-card">
                            <div class="objection-header dark">💸 "BYUDJET YO'Q"</div>
                            <div class="objection-body">
                                <div class="objection-tags">
                                    <span class="tag">REFRAMING</span>
                                    <span class="tag">OPTIONS</span>
                                </div>
                                <div class="objection-script">
                                    "Tushundim. <strong>Qachon</strong> yangi byudjet?<br><br>
                                    Aytgancha, bir nechta variant bor:<br>
                                    • <strong>Bo'lib to'lash</strong> — oyiga [X] dan<br>
                                    • <strong>Kichik paket</strong>dan boshlash<br>
                                    • <strong>Bepul sinov</strong> — hoziroq<br><br>
                                    Qaysi variant qulayroq?"
                                </div>
                                <button @click="copyScript('Tushundim. Qachon yangi byudjet? Bir nechta variant bor: Bo\'lib to\'lash, Kichik paketdan boshlash, Bepul sinov. Qaysi variant qulayroq?')" class="objection-copy">
                                    <ClipboardDocumentIcon class="w-4 h-4" /> Nusxalash
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="key-point">
                        <h4>💡 E'TIROZ = "MENGA KO'PROQ MA'LUMOT KERAK"</h4>
                        <p>
                            Har bir e'tirozdan keyin: <strong>"Shu savolga javob berdimmi? → Demak, davom etamiz..."</strong><br><br>
                            <strong>Muhim:</strong> E'tiroz — bu "yo'q" emas! Bu mijozning "menga yordam bering" degan signali.
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==================== BOSQICH 5: YOPISH ==================== -->
            <section class="section" id="section-5" :class="{ collapsed: collapsedSections['section-5'] }">
                <div class="section-header" @click="toggleSection('section-5')">
                    <div class="section-icon green">🎯</div>
                    <div class="section-info">
                        <div class="section-title">5-BOSQICH: BITIMNI YOPISH</div>
                        <div class="section-subtitle">12 ta kuchli yopish texnikasi</div>
                    </div>
                    <div class="section-badges">
                        <span class="section-badge gold">12 texnika</span>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <div class="closing-grid">
                        <div class="closing-card">
                            <div class="closing-number">1</div>
                            <div class="closing-name">DIRECT CLOSE</div>
                            <div class="closing-desc">To'g'ridan-to'g'ri so'rang</div>
                            <div class="closing-script">"[ISM], barcha savollaringizga javob berdim.<br><strong>Boshlaymizmi?</strong>"</div>
                            <div class="closing-when">⏰ Mijoz tayyor, barcha savollarga javob berilgan</div>
                            <button @click="copyScript('[ISM], barcha savollaringizga javob berdim. Boshlaymizmi?')" class="closing-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="closing-card">
                            <div class="closing-number">2</div>
                            <div class="closing-name">ALTERNATIVE CLOSE</div>
                            <div class="closing-desc">Tanlov bering — ikkalasi ham "ha"</div>
                            <div class="closing-script">"Qaysi variant sizga qulayroq —<br><strong>asosiy paket</strong>mi yoki <strong>premium</strong>?"</div>
                            <div class="closing-when">⏰ Mijoz ikkilanayotganda</div>
                            <button @click="copyScript('Qaysi variant sizga qulayroq — asosiy paketmi yoki premium?')" class="closing-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="closing-card">
                            <div class="closing-number">3</div>
                            <div class="closing-name">URGENCY CLOSE</div>
                            <div class="closing-desc">Shoshilinchlik yarating — SCARCITY</div>
                            <div class="closing-script">"Bu narx faqat <strong>shu hafta</strong>.<br>Dushanbadan <strong>30% qimmatroq</strong>."</div>
                            <div class="closing-when">⏰ Qo'shimcha motivatsiya kerak</div>
                            <button @click="copyScript('Bu narx faqat shu hafta. Dushanbadan 30% qimmatroq.')" class="closing-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="closing-card">
                            <div class="closing-number">4</div>
                            <div class="closing-name">SUMMARY CLOSE</div>
                            <div class="closing-desc">Hammasini xulosa qiling</div>
                            <div class="closing-script">"Xulosa: Sizga [MUAMMO] hal kerak.<br>Bizning [YECHIM] aynan bunga.<br><strong>Hammasiga rozimisiz?</strong>"</div>
                            <div class="closing-when">⏰ Ko'p nuqtalar muhokama qilingan</div>
                            <button @click="copyScript('Xulosa: Sizga [MUAMMO] hal kerak. Bizning [YECHIM] aynan bunga. Hammasiga rozimisiz?')" class="closing-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="closing-card">
                            <div class="closing-number">5</div>
                            <div class="closing-name">ASSUMPTIVE CLOSE</div>
                            <div class="closing-desc">"Ha" degan deb faraz qiling</div>
                            <div class="closing-script">"Ajoyib, demak boshlaymiz!<br>Shartnomani <strong>qaysi emailga</strong> jo'natay?"</div>
                            <div class="closing-when">⏰ Mijoz "ha" signallari bergan</div>
                            <button @click="copyScript('Ajoyib, demak boshlaymiz! Shartnomani qaysi emailga jo\'natay?')" class="closing-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="closing-card">
                            <div class="closing-number">6</div>
                            <div class="closing-name">SCALE CLOSE</div>
                            <div class="closing-desc">1-10 shkala bilan aniqlang</div>
                            <div class="closing-script">"<strong>1 dan 10 gacha</strong> — qanchalik tayyorsiz?<br><strong>Nima 10 ga yetkazadi?</strong>"</div>
                            <div class="closing-when">⏰ Yashirin e'tirozni topish uchun</div>
                            <button @click="copyScript('1 dan 10 gacha — qanchalik tayyorsiz? Nima 10 ga yetkazadi?')" class="closing-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="key-point">
                        <h4>💡 YOPISH QOIDASI</h4>
                        <p>
                            Savoldan keyin — <strong>JIM TURING!</strong> Ko'p sotuvchilar javobni kutmasdan gapira boshlaydi.<br><br>
                            <strong>Ketma-ketlik:</strong> Direct → Alternative → Summary → Scale → E'tiroz topish → Qayta yopish
                        </p>
                    </div>
                </div>
            </section>

            <!-- ==================== BOSQICH 6: FOLLOW-UP ==================== -->
            <section class="section" id="section-6" :class="{ collapsed: collapsedSections['section-6'] }">
                <div class="section-header" @click="toggleSection('section-6')">
                    <div class="section-icon teal">📅</div>
                    <div class="section-info">
                        <div class="section-title">6-BOSQICH: YAKUNLASH & FOLLOW-UP</div>
                        <div class="section-subtitle">Bitimdan keyin va kutish davrida</div>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <div class="result-cards">
                        <!-- BITIM BO'LDI -->
                        <div class="result-card success">
                            <div class="result-header">
                                <span class="result-icon">✅</span>
                                <span class="result-title">BITIM BO'LDI</span>
                            </div>
                            <div class="result-body">
                                <div class="result-script">
                                    "Ajoyib qaror, [ISM]!<br><br>
                                    <strong>Kelishuvimiz:</strong><br>
                                    1️⃣ Men shartnoma jo'nataman — bugun<br>
                                    2️⃣ Siz to'lov qilasiz — [SANA]<br>
                                    3️⃣ Start — [SANA]<br><br>
                                    <strong>Hamkorlik uchun rahmat!</strong>"
                                </div>
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-day success">0-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📧 Shartnoma + Welcome</div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-day success">1-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📞 Onboarding qo'ng'iroq</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KUTISH -->
                        <div class="result-card pending">
                            <div class="result-header">
                                <span class="result-icon">⏳</span>
                                <span class="result-title">KUTISH</span>
                            </div>
                            <div class="result-body">
                                <div class="result-script">
                                    "Rahmat, [ISM]!<br><br>
                                    1️⃣ Men material jo'nataman — bugun<br>
                                    2️⃣ [KUN]da qayta qo'ng'iroq qilaman"
                                </div>
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-day pending">0-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📧 Material</div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-day pending">2-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📞 Qo'ng'iroq</div>
                                            <div class="timeline-detail">"Ko'rib chiqdingizmi?"</div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-day pending">5-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📧 Qiymat</div>
                                            <div class="timeline-detail">Case study</div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-day pending">7-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📞 Qaror</div>
                                            <div class="timeline-detail">"Qaroringiz?"</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- RAD -->
                        <div class="result-card rejected">
                            <div class="result-header">
                                <span class="result-icon">❌</span>
                                <span class="result-title">RAD</span>
                            </div>
                            <div class="result-body">
                                <div class="result-script">
                                    "Tushundim, [ISM].<br><br>
                                    Kelajakda ehtiyoj bo'lsa — qo'ng'iroq qiling.<br>
                                    Foydali material yuborib turaymi?"
                                </div>
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-day rejected">30-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📧 Foydali kontent</div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-day rejected">60-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📧 Yangilik</div>
                                        </div>
                                    </div>
                                    <div class="timeline-item">
                                        <div class="timeline-day rejected">90-kun</div>
                                        <div class="timeline-content">
                                            <div class="timeline-action">📞 Qayta qo'ng'iroq</div>
                                            <div class="timeline-detail">1-BOSQICHdan</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== FAQ ==================== -->
            <section class="section" id="section-faq" :class="{ collapsed: collapsedSections['section-faq'] }">
                <div class="section-header" @click="toggleSection('section-faq')">
                    <div class="section-icon cyan">❓</div>
                    <div class="section-info">
                        <div class="section-title">TEZ-TEZ BERILADIGAN SAVOLLAR</div>
                        <div class="section-subtitle">Mijozlar eng ko'p so'raydigan 10 ta savol</div>
                    </div>
                    <div class="section-badges">
                        <span class="section-badge gold">10 ta FAQ</span>
                    </div>
                    <div class="section-toggle">▼</div>
                </div>

                <div class="section-content">
                    <div class="faq-grid">
                        <div class="faq-card">
                            <div class="faq-question">1. "Qanday kafolat berasiz?"</div>
                            <div class="faq-answer">
                                "Biz 100% natija kafolatini beramiz. Agar birinchi 30 kun ichida siz kutgan natijani ko'rmasangiz, pulingizni qaytarib beramiz. Bundan tashqari, har bir mijozimiz bilan alohida shartnoma tuzamiz va barcha shartlar yozma ravishda kelishiladi."
                            </div>
                            <button @click="copyScript('Biz 100% natija kafolatini beramiz. Agar birinchi 30 kun ichida siz kutgan natijani ko\'rmasangiz, pulingizni qaytarib beramiz.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">2. "Qancha vaqtda natija ko'ramiz?"</div>
                            <div class="faq-answer">
                                "Odatda birinchi natijalar 1-2 hafta ichida ko'rinadi. To'liq natijaga erishish uchun 1-3 oy kerak bo'ladi. Lekin bu sizning faolligingiz va resurslaringizga ham bog'liq. Biz har qadamda sizni qo'llab-quvvatlaymiz."
                            </div>
                            <button @click="copyScript('Odatda birinchi natijalar 1-2 hafta ichida ko\'rinadi. To\'liq natijaga erishish uchun 1-3 oy kerak bo\'ladi.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">3. "Boshqa mijozlaringiz kimlar?"</div>
                            <div class="faq-answer">
                                "Biz <span class='highlight'>[sohada]</span> 50 dan ortiq kompaniya bilan ishlaganmiz. Masalan, <span class='highlight'>[Kompaniya nomi]</span> biz bilan ishlagandan keyin <span class='highlight'>[natija]</span>ga erishdi. Xohlasangiz, ularning fikrlarini ko'rsataman."
                            </div>
                            <button @click="copyScript('Biz [sohada] 50 dan ortiq kompaniya bilan ishlaganmiz. Masalan, [Kompaniya nomi] biz bilan ishlagandan keyin [natija]ga erishdi.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">4. "Nima uchun sizni tanlashim kerak?"</div>
                            <div class="faq-answer">
                                "Bizning 3 ta asosiy afzalligimiz bor: Birinchidan, biz <span class='highlight'>[soha]</span>da 5+ yillik tajribaga egamiz. Ikkinchidan, har bir mijozga shaxsiy yondashamiz. Uchinchidan, natija bo'lmasa — pul qaytariladi."
                            </div>
                            <button @click="copyScript('Bizning 3 ta asosiy afzalligimiz bor: 5+ yillik tajriba, shaxsiy yondashuv, natija kafolati.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">5. "To'lov qanday amalga oshiriladi?"</div>
                            <div class="faq-answer">
                                "Biz sizga qulay bo'lgan turli to'lov usullarini taklif qilamiz: naqd, bank o'tkazmasi, karta orqali. Bundan tashqari, bo'lib to'lash imkoniyati ham mavjud — 50% oldindan, qolgan 50% ish yakunlangandan keyin."
                            </div>
                            <button @click="copyScript('Turli to\'lov usullari: naqd, bank o\'tkazmasi, karta. Bo\'lib to\'lash imkoniyati ham mavjud.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">6. "Qo'shimcha xarajatlar bormi?"</div>
                            <div class="faq-answer">
                                "Yo'q, biz kelishilgan narxdan tashqari hech qanday qo'shimcha to'lov so'ramaymiz. Barcha xarajatlar oldindan aytiladi va shartnomada yoziladi. Yashirin to'lovlar yo'q — bu bizning asosiy tamoyillarimizdan biri."
                            </div>
                            <button @click="copyScript('Yo\'q, qo\'shimcha to\'lovlar yo\'q. Barcha xarajatlar oldindan kelishiladi va shartnomada yoziladi.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">7. "Qo'llab-quvvatlash xizmati qanday?"</div>
                            <div class="faq-answer">
                                "Biz 24/7 qo'llab-quvvatlash xizmatini taqdim etamiz. Telefon, Telegram yoki email orqali biz bilan bog'lanishingiz mumkin. Har qanday savolingizga 2 soat ichida javob beramiz."
                            </div>
                            <button @click="copyScript('24/7 qo\'llab-quvvatlash: telefon, Telegram, email. 2 soat ichida javob kafolati.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">8. "Shartnoma bekor qilsa bo'ladimi?"</div>
                            <div class="faq-answer">
                                "Ha, albatta. Agar birinchi 14 kun ichida xizmatimiz sizga mos kelmasa, shartnomani bekor qilishingiz va pulingizni qaytarib olishingiz mumkin. Bu bizning ishonch kafolatimiz."
                            </div>
                            <button @click="copyScript('Ha, 14 kun ichida shartnomani bekor qilish va pul qaytarish mumkin.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">9. "Qachon boshlasak bo'ladi?"</div>
                            <div class="faq-answer">
                                "Bugunning o'zida boshlashimiz mumkin! Shartnoma imzolanishi bilan biz darhol ishga kirishamiz. Onboarding jarayoni 1 kun oladi va keyingi kuniyoq siz birinchi natijalarni ko'ra boshlaysiz."
                            </div>
                            <button @click="copyScript('Bugunning o\'zida boshlashimiz mumkin! Onboarding 1 kun, keyingi kuni natija.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="faq-card">
                            <div class="faq-question">10. "Boshqa taklif bilan taqqoslasam bo'ladimi?"</div>
                            <div class="faq-answer">
                                "Albatta, taqqoslang! Biz bundan qo'rqmaymiz, chunki o'z sifatimizga ishonchimiz komil. Faqat bir narsa — taqqoslashda nafaqat narxni, balki tajriba, kafolat va natijalarni ham ko'ring."
                            </div>
                            <button @click="copyScript('Albatta, taqqoslang! Taqqoslashda narx, tajriba, kafolat va natijalarni ko\'ring.')" class="faq-copy">
                                <ClipboardDocumentIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="key-point">
                        <h4>💡 FAQ MASLAHATLAR</h4>
                        <p>
                            • Har doim mijozning savolini to'liq tinglang, keyin javob bering<br>
                            • Javob berishdan oldin savolni qayta takrorlang — bu ishonchni oshiradi<br>
                            • Agar javobni bilmasangiz — "Men aniqlab, sizga qaytaman" deng<br>
                            • Har bir javobda bitta konkret dalil yoki raqam keltiring
                        </p>
                    </div>
                </div>
            </section>

            <!-- SUMMARY -->
            <div class="summary">
                <h2>🏆 MUVAFFAQIYAT FORMULASI</h2>
                <div class="summary-formula">
                    <div class="formula-row">
                        <div class="formula-item">ALOQA</div>
                        <span class="formula-operator">+</span>
                        <div class="formula-item">SPIN</div>
                        <span class="formula-operator">+</span>
                        <div class="formula-item">FAB</div>
                        <span class="formula-operator">+</span>
                        <div class="formula-item">LAER</div>
                        <span class="formula-operator">=</span>
                        <div class="formula-item success">BITIM</div>
                    </div>
                </div>

                <div class="summary-stats">
                    <div class="summary-stat">
                        <div class="summary-stat-value">70%</div>
                        <div class="summary-stat-label">Tinglash</div>
                    </div>
                    <div class="summary-stat">
                        <div class="summary-stat-value">30%</div>
                        <div class="summary-stat-label">Gapirish</div>
                    </div>
                    <div class="summary-stat">
                        <div class="summary-stat-value">6</div>
                        <div class="summary-stat-label">Bosqich</div>
                    </div>
                    <div class="summary-stat">
                        <div class="summary-stat-value">12</div>
                        <div class="summary-stat-label">Yopish usuli</div>
                    </div>
                </div>

                <p class="summary-note">
                    <strong>Esda tuting:</strong> Eng kuchli texnika — <strong>HAQIQIY QIZIQISH VA YORDAM BERISH ISTAGI</strong>.<br>
                    Texnikalar faqat vosita. Mijozga <strong>haqiqiy qiymat</strong> bering!
                </p>
            </div>
        </div>
    </BusinessLayout>
</template>

<style scoped>
/* ==================== VARIABLES ==================== */
.sales-arsenal {
    --gold: #ffd700;
    --gold-dark: #b8860b;
    --bg-dark: #0f172a;
    --bg-card: rgba(255,255,255,0.03);
    --border: rgba(255,255,255,0.1);
    --text: #ffffff;
    --text-muted: #94a3b8;
}

/* Light mode overrides */
@media (prefers-color-scheme: light) {
    .sales-arsenal {
        --bg-dark: #f8fafc;
        --bg-card: rgba(0,0,0,0.02);
        --border: rgba(0,0,0,0.1);
        --text: #1e293b;
        --text-muted: #64748b;
    }
}

:root:not(.dark) .sales-arsenal {
    --bg-dark: #f8fafc;
    --bg-card: rgba(0,0,0,0.02);
    --border: rgba(0,0,0,0.1);
    --text: #1e293b;
    --text-muted: #64748b;
}

.dark .sales-arsenal {
    --bg-dark: #0f172a;
    --bg-card: rgba(255,255,255,0.03);
    --border: rgba(255,255,255,0.1);
    --text: #ffffff;
    --text-muted: #94a3b8;
}

/* ==================== HEADER ==================== */
.header {
    text-align: center;
    padding: 40px 30px;
    background: linear-gradient(135deg, rgba(255,215,0,0.15), rgba(255,165,0,0.05));
    border: 2px solid var(--gold);
    border-radius: 20px;
    margin-bottom: 20px;
    position: relative;
    overflow: hidden;
}

.header-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #000;
    padding: 6px 20px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.8rem;
    margin-bottom: 12px;
}

.header h1 {
    font-size: 2.2rem;
    color: var(--gold);
    margin-bottom: 8px;
    text-shadow: 0 0 30px rgba(255,215,0,0.5);
}

.header p {
    color: var(--text-muted);
    font-size: 1rem;
    margin-bottom: 20px;
}

.header-stats {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.stat-card {
    background: rgba(0,0,0,0.3);
    border: 1px solid var(--border);
    padding: 12px 20px;
    border-radius: 12px;
    text-align: center;
}

.dark .stat-card {
    background: rgba(0,0,0,0.3);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--gold);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-muted);
}

/* ==================== NAVIGATION ==================== */
.nav {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(15,23,42,0.95);
    padding: 12px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

:root:not(.dark) .nav {
    background: rgba(248,250,252,0.95);
}

.nav-btn {
    padding: 10px 18px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    font-weight: bold;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.nav-btn.gold { background: linear-gradient(135deg, var(--gold), var(--gold-dark)); color: #000; }
.nav-btn.outline { background: transparent; color: var(--gold); border: 2px solid var(--gold); }
.nav-btn.red { background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; }

.nav-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}

/* ==================== SECTION ==================== */
.section {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.section:hover {
    border-color: rgba(255,215,0,0.3);
}

.section-header {
    padding: 18px 24px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid var(--border);
    transition: all 0.3s ease;
}

.section-header:hover {
    background: rgba(255,215,0,0.05);
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.section-icon.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.section-icon.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.section-icon.orange { background: linear-gradient(135deg, #f97316, #ea580c); }
.section-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
.section-icon.green { background: linear-gradient(135deg, #22c55e, #16a34a); }
.section-icon.teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.section-icon.cyan { background: linear-gradient(135deg, #06b6d4, #0891b2); }

.section-info { flex: 1; }

.section-title {
    font-size: 1.1rem;
    font-weight: bold;
    color: var(--text);
    margin-bottom: 4px;
}

.section-subtitle {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.section-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.section-badge {
    padding: 4px 12px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
}

.section-badge.gold { background: rgba(255,215,0,0.2); color: var(--gold); }
.section-badge.new { background: rgba(34,197,94,0.2); color: #22c55e; }
.section-badge.pro { background: rgba(139,92,246,0.2); color: #8b5cf6; }

.section-toggle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.3s ease;
    color: var(--text);
}

.section.collapsed .section-toggle {
    transform: rotate(-90deg);
}

.section-content {
    padding: 24px;
}

.section.collapsed .section-content {
    display: none;
}

/* ==================== FLOW CHART ==================== */
.flow {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
}

.flow-node {
    padding: 20px 28px;
    border-radius: 16px;
    text-align: center;
    max-width: 600px;
    width: 100%;
    position: relative;
    line-height: 1.6;
    color: #fff;
}

.flow-node.start { background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 30px; }
.flow-node.script { background: linear-gradient(135deg, #22c55e, #16a34a); }
.flow-node.decision { background: linear-gradient(135deg, #f97316, #ea580c); }
.flow-node.situation { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.flow-node.problem { background: linear-gradient(135deg, #ef4444, #dc2626); }
.flow-node.implication { background: linear-gradient(135deg, #f97316, #ea580c); }
.flow-node.need-payoff { background: linear-gradient(135deg, #22c55e, #16a34a); color: #fff; }
.flow-node.presentation { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.flow-node .label {
    position: absolute;
    top: -12px;
    left: 20px;
    background: var(--gold);
    color: #000;
    padding: 4px 14px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.flow-node .label.dark {
    background: #000;
    color: var(--gold);
}

.script-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.script-text {
    flex: 1;
    text-align: left;
}

.copy-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    color: #fff;
    transition: all 0.2s;
}

.copy-btn:hover {
    background: rgba(255,255,255,0.3);
}

.copy-btn.dark {
    color: #fff;
}

.highlight {
    background: rgba(255,215,0,0.3);
    padding: 2px 6px;
    border-radius: 4px;
    color: var(--gold);
}

.flow-arrow {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0;
    color: var(--gold);
}

.flow-arrow-line {
    width: 4px;
    height: 30px;
    background: linear-gradient(to bottom, var(--gold), var(--gold-dark));
    border-radius: 2px;
}

.flow-branches {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    width: 100%;
    margin-top: 20px;
}

.flow-branch {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 160px;
    max-width: 220px;
    flex: 1;
}

.branch-label {
    background: rgba(255,215,0,0.2);
    color: var(--gold);
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.8rem;
    margin-bottom: 8px;
}

.branch-response {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    padding: 10px 14px;
    border-radius: 10px;
    text-align: center;
    font-size: 0.8rem;
    width: 100%;
    margin-bottom: 8px;
}

.branch-response.positive { background: linear-gradient(135deg, #22c55e, #16a34a); }
.branch-response.neutral { background: linear-gradient(135deg, #f97316, #ea580c); }
.branch-response.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

.branch-action {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
    padding: 10px 14px;
    border-radius: 10px;
    text-align: center;
    font-size: 0.8rem;
    width: 100%;
    line-height: 1.4;
    margin-bottom: 8px;
}

.branch-goto {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: bold;
}

.branch-goto.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
.branch-goto.loop { background: linear-gradient(135deg, #f97316, #ea580c); }

/* ==================== KEY POINT ==================== */
.key-point {
    background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,165,0,0.05));
    border: 1px solid rgba(255,215,0,0.3);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.key-point h4 {
    color: var(--gold);
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
}

.key-point p {
    color: var(--text-muted);
    line-height: 1.7;
    font-size: 0.9rem;
}

/* ==================== METHOD CARD ==================== */
.method-card {
    background: linear-gradient(135deg, rgba(255,215,0,0.05), rgba(255,165,0,0.02));
    border: 1px solid rgba(255,215,0,0.2);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 24px;
}

.method-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,215,0,0.1);
}

.method-icon {
    font-size: 2.5rem;
    margin-bottom: 8px;
}

.method-name {
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--gold);
    margin-bottom: 4px;
}

.method-origin {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.method-body {
    padding: 20px;
}

.method-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}

.method-step {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    flex: 1;
    min-width: 200px;
}

.dark .method-step {
    background: rgba(0,0,0,0.3);
}

.step-letter {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #000;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1rem;
    flex-shrink: 0;
}

.step-title {
    font-weight: bold;
    color: var(--text);
    margin-bottom: 2px;
    font-size: 0.9rem;
}

.step-desc {
    color: var(--text-muted);
    font-size: 0.8rem;
}

/* ==================== OBJECTION CARDS ==================== */
.objections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
}

.objection-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}

.objection-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
    color: #fff;
    font-size: 0.9rem;
}

.objection-header.red { background: linear-gradient(135deg, #ef4444, #dc2626); }
.objection-header.orange { background: linear-gradient(135deg, #f97316, #ea580c); }
.objection-header.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.objection-header.purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.objection-header.green { background: linear-gradient(135deg, #22c55e, #16a34a); }
.objection-header.dark { background: linear-gradient(135deg, #475569, #334155); }

.objection-body {
    padding: 16px;
}

.objection-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}

.tag {
    background: rgba(255,215,0,0.15);
    color: var(--gold);
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: bold;
}

.objection-script {
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    padding: 14px;
    color: var(--text);
    line-height: 1.6;
    font-size: 0.85rem;
    margin-bottom: 12px;
}

.dark .objection-script {
    background: rgba(0,0,0,0.4);
}

.objection-copy {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,215,0,0.1);
    border: 1px solid rgba(255,215,0,0.3);
    color: var(--gold);
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.8rem;
    font-weight: bold;
    transition: all 0.2s;
}

.objection-copy:hover {
    background: rgba(255,215,0,0.2);
}

/* ==================== CLOSING CARDS ==================== */
.closing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.closing-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px;
    position: relative;
}

.closing-card:hover {
    border-color: var(--gold);
}

.closing-number {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--gold), var(--gold-dark));
    color: #000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 12px;
}

.closing-name {
    color: var(--gold);
    font-weight: bold;
    font-size: 0.95rem;
    margin-bottom: 6px;
}

.closing-desc {
    color: var(--text-muted);
    font-size: 0.8rem;
    margin-bottom: 10px;
}

.closing-script {
    background: rgba(0,0,0,0.2);
    border-radius: 8px;
    padding: 12px;
    color: var(--text);
    font-style: italic;
    line-height: 1.5;
    margin-bottom: 10px;
    font-size: 0.85rem;
}

.dark .closing-script {
    background: rgba(0,0,0,0.4);
}

.closing-when {
    color: #22c55e;
    font-size: 0.75rem;
}

.closing-copy {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(255,215,0,0.1);
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    color: var(--gold);
    transition: all 0.2s;
}

.closing-copy:hover {
    background: rgba(255,215,0,0.2);
}

/* ==================== RESULT CARDS ==================== */
.result-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.result-card {
    border-radius: 12px;
    overflow: hidden;
}

.result-card.success { border: 1px solid #22c55e; }
.result-card.pending { border: 1px solid #f97316; }
.result-card.rejected { border: 1px solid #ef4444; }

.result-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.result-card.success .result-header { background: rgba(34,197,94,0.1); }
.result-card.pending .result-header { background: rgba(249,115,22,0.1); }
.result-card.rejected .result-header { background: rgba(239,68,68,0.1); }

.result-icon { font-size: 1.2rem; }
.result-title { font-weight: bold; color: var(--text); }

.result-body { padding: 16px; }

.result-script {
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    padding: 14px;
    color: var(--text);
    line-height: 1.6;
    font-size: 0.85rem;
    margin-bottom: 16px;
}

.dark .result-script {
    background: rgba(0,0,0,0.4);
}

.timeline {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: rgba(255,255,255,0.02);
    border-radius: 8px;
}

.timeline-day {
    padding: 6px 12px;
    border-radius: 16px;
    font-weight: bold;
    font-size: 0.75rem;
    min-width: 70px;
    text-align: center;
    color: #fff;
}

.timeline-day.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
.timeline-day.pending { background: linear-gradient(135deg, #f97316, #ea580c); }
.timeline-day.rejected { background: #475569; }

.timeline-action { font-weight: bold; color: var(--text); font-size: 0.85rem; }
.timeline-detail { font-size: 0.75rem; color: var(--text-muted); }

/* ==================== FAQ CARDS ==================== */
.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
}

.faq-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 18px;
    position: relative;
}

.faq-card:hover {
    border-color: #06b6d4;
}

.faq-question {
    font-weight: bold;
    color: #06b6d4;
    margin-bottom: 10px;
    font-size: 0.95rem;
}

.faq-answer {
    color: var(--text);
    line-height: 1.6;
    font-size: 0.85rem;
}

.faq-answer .highlight {
    background: rgba(6,182,212,0.2);
    color: #06b6d4;
}

.faq-copy {
    position: absolute;
    top: 16px;
    right: 16px;
    background: rgba(6,182,212,0.1);
    border: none;
    padding: 8px;
    border-radius: 8px;
    cursor: pointer;
    color: #06b6d4;
    transition: all 0.2s;
}

.faq-copy:hover {
    background: rgba(6,182,212,0.2);
}

/* ==================== SUMMARY ==================== */
.summary {
    background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,165,0,0.05));
    border: 2px solid var(--gold);
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    margin-top: 30px;
}

.summary h2 {
    color: var(--gold);
    font-size: 1.5rem;
    margin-bottom: 24px;
}

.summary-formula {
    background: rgba(0,0,0,0.2);
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
}

.dark .summary-formula {
    background: rgba(0,0,0,0.4);
}

.formula-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    font-size: 1rem;
}

.formula-item {
    background: rgba(255,215,0,0.1);
    padding: 10px 18px;
    border-radius: 10px;
    color: var(--text);
    font-weight: bold;
}

.formula-item.success {
    background: rgba(34,197,94,0.2);
    color: #22c55e;
}

.formula-operator {
    color: var(--gold);
    font-size: 1.3rem;
    font-weight: bold;
}

.summary-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}

.summary-stat { text-align: center; }

.summary-stat-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: var(--gold);
}

.summary-stat-label {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.summary-note {
    color: var(--text-muted);
    font-size: 0.95rem;
    line-height: 1.7;
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 768px) {
    .header h1 { font-size: 1.6rem; }
    .header-stats { gap: 10px; }
    .stat-card { padding: 10px 14px; }
    .nav { flex-direction: column; align-items: stretch; }
    .flow-branches { flex-direction: column; align-items: center; }
    .flow-branch { max-width: 100%; width: 100%; }
    .section-header { flex-wrap: wrap; }
    .section-badges { width: 100%; margin-top: 8px; }
    .summary { padding: 24px 16px; }
    .summary-stats { gap: 20px; }
}
</style>
