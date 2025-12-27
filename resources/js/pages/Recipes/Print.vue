<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, computed } from 'vue';

const props = defineProps<{
    recipe: {
        id: number;
        name: string;
        description: string;
        image_url: string | null;
        yield_quantity: number;
        yield_unit: string;
        total_cost: number;
        selling_price: number;
        labor_cost: number;
        overhead_cost: number;
        packaging_cost: number;
        ingredients: Array<{
            id: number;
            name: string;
            pivot: {
                quantity: number;
                unit: string;
            };
        }>;
        steps: Array<{
            id: number;
            step_number: number;
            instruction: string;
            image_url: string | null;
        }>;
    };
}>();

// คำนวณต้นทุนวัตถุดิบ
const ingredientCost = computed(() => {
    const total = props.recipe.total_cost || 0;
    const labor = props.recipe.labor_cost || 0;
    const overhead = props.recipe.overhead_cost || 0;
    const packaging = props.recipe.packaging_cost || 0;
    return (total - labor - overhead - packaging).toFixed(2);
});

// คำนวณต้นทุนต่อหน่วย
const costPerUnit = computed(() => {
    if (props.recipe.yield_quantity > 0 && props.recipe.total_cost > 0) {
        return (props.recipe.total_cost / props.recipe.yield_quantity).toFixed(2);
    }
    return '0.00';
});

// คำนวณกำไร
const profit = computed(() => {
    if (props.recipe.selling_price && props.recipe.total_cost) {
        const profitPerYield = props.recipe.selling_price - (props.recipe.total_cost / props.recipe.yield_quantity);
        return profitPerYield.toFixed(2);
    }
    return null;
});

// คำนวณ margin %
const profitMargin = computed(() => {
    if (props.recipe.selling_price && props.recipe.total_cost) {
        const costPerItem = props.recipe.total_cost / props.recipe.yield_quantity;
        const margin = ((props.recipe.selling_price - costPerItem) / props.recipe.selling_price) * 100;
        return margin.toFixed(1);
    }
    return null;
});

// วันที่พิมพ์
const printDate = new Date().toLocaleDateString('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
});

// Auto print on mount
onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});

const goBack = () => {
    window.history.back();
};

const printPage = () => {
    window.print();
};
</script>

<template>
    <Head :title="`พิมพ์ SOP - ${recipe.name}`" />

    <div class="print-container">
        <!-- Screen-only controls -->
        <div class="no-print fixed top-4 right-4 flex gap-2 z-50">
            <button 
                @click="printPage" 
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg flex items-center gap-2"
            >
                🖨️ พิมพ์
            </button>
            <button 
                @click="goBack" 
                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg shadow-lg flex items-center gap-2"
            >
                ← กลับ
            </button>
        </div>

        <!-- Print Content -->
        <div class="print-content">
            <!-- ===== HEADER SECTION ===== -->
            <header class="header-section">
                <div class="header-top">
                    <div class="header-left">
                        <!-- Recipe Image -->
                        <div class="recipe-image-container">
                            <img v-if="recipe.image_url" :src="recipe.image_url" :alt="recipe.name" class="recipe-main-image" />
                            <div v-else class="recipe-image-placeholder">
                                <span>🍽️</span>
                            </div>
                        </div>
                        <div class="recipe-title-info">
                            <h1 class="recipe-title">{{ recipe.name }}</h1>
                            <p class="recipe-subtitle">Standard Operating Procedure (SOP)</p>
                            <p v-if="recipe.description" class="recipe-description">{{ recipe.description }}</p>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="doc-info">
                            <p class="doc-number">SOP-{{ String(recipe.id).padStart(4, '0') }}</p>
                            <p class="doc-date">{{ printDate }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- ===== SUMMARY CARDS ===== -->
            <section class="summary-section">
                <div class="summary-cards">
                    <div class="summary-card yield-card">
                        <div class="card-icon">📦</div>
                        <div class="card-content">
                            <p class="card-label">ผลผลิต</p>
                            <p class="card-value">{{ recipe.yield_quantity }} {{ recipe.yield_unit }}</p>
                        </div>
                    </div>
                    <div class="summary-card cost-card">
                        <div class="card-icon">💵</div>
                        <div class="card-content">
                            <p class="card-label">ต้นทุนรวม</p>
                            <p class="card-value">฿{{ Number(recipe.total_cost).toFixed(2) }}</p>
                        </div>
                    </div>
                    <div class="summary-card unit-cost-card">
                        <div class="card-icon">📊</div>
                        <div class="card-content">
                            <p class="card-label">ต้นทุน/หน่วย</p>
                            <p class="card-value">฿{{ costPerUnit }}</p>
                        </div>
                    </div>
                    <div class="summary-card price-card">
                        <div class="card-icon">🏷️</div>
                        <div class="card-content">
                            <p class="card-label">ราคาขาย</p>
                            <p class="card-value">{{ recipe.selling_price ? `฿${recipe.selling_price}` : '-' }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Profit Banner -->
                <div v-if="profit && profitMargin" class="profit-banner">
                    <span class="profit-item">💰 กำไร/หน่วย: <strong>฿{{ profit }}</strong></span>
                    <span class="profit-divider">|</span>
                    <span class="profit-item">📈 Margin: <strong>{{ profitMargin }}%</strong></span>
                </div>
            </section>

            <!-- ===== MAIN CONTENT: TWO COLUMNS ===== -->
            <div class="main-content">
                <!-- LEFT COLUMN: Ingredients & Cost -->
                <div class="left-column">
                    <!-- Ingredients Table -->
                    <section class="ingredients-section">
                        <h2 class="section-title ingredients-title">
                            <span class="title-icon">🥗</span>
                            วัตถุดิบ
                            <span class="title-count">({{ recipe.ingredients.length }} รายการ)</span>
                        </h2>
                        <table class="ingredients-table">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th class="col-name">รายการ</th>
                                    <th class="col-qty">จำนวน</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(ingredient, index) in recipe.ingredients" :key="ingredient.id">
                                    <td class="col-num">{{ index + 1 }}</td>
                                    <td class="col-name">{{ ingredient.name }}</td>
                                    <td class="col-qty">{{ ingredient.pivot.quantity }} {{ ingredient.pivot.unit }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>

                    <!-- Cost Breakdown -->
                    <section class="cost-section">
                        <h2 class="section-title cost-title">
                            <span class="title-icon">💰</span>
                            รายละเอียดต้นทุน
                        </h2>
                        <table class="cost-table">
                            <tbody>
                                <tr>
                                    <td>ต้นทุนวัตถุดิบ</td>
                                    <td class="cost-value">฿{{ ingredientCost }}</td>
                                </tr>
                                <tr>
                                    <td>ค่าแรงงาน</td>
                                    <td class="cost-value">฿{{ Number(recipe.labor_cost || 0).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td>ค่าใช้จ่ายการผลิต</td>
                                    <td class="cost-value">฿{{ Number(recipe.overhead_cost || 0).toFixed(2) }}</td>
                                </tr>
                                <tr>
                                    <td>ค่าบรรจุภัณฑ์</td>
                                    <td class="cost-value">฿{{ Number(recipe.packaging_cost || 0).toFixed(2) }}</td>
                                </tr>
                                <tr class="total-row">
                                    <td>รวมทั้งหมด</td>
                                    <td class="cost-value total-value">฿{{ Number(recipe.total_cost).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                </div>

                <!-- RIGHT COLUMN: Steps -->
                <div class="right-column">
                    <section class="steps-section">
                        <h2 class="section-title steps-title">
                            <span class="title-icon">📝</span>
                            ขั้นตอนการทำ
                            <span class="title-count">({{ recipe.steps.length }} ขั้นตอน)</span>
                        </h2>
                        <div class="steps-list">
                            <div v-for="step in recipe.steps" :key="step.id" class="step-item">
                                <div class="step-number">{{ step.step_number }}</div>
                                <div class="step-content">
                                    <p class="step-instruction">{{ step.instruction }}</p>
                                    <img v-if="step.image_url" :src="step.image_url" :alt="`ขั้นตอนที่ ${step.step_number}`" class="step-image" />
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- ===== FOOTER: Signatures ===== -->
            <footer class="footer-section">
                <div class="signature-grid">
                    <div class="signature-box">
                        <p class="signature-label">ผู้จัดทำ</p>
                        <div class="signature-line"></div>
                        <p class="signature-name">ชื่อ: _________________</p>
                        <p class="signature-date">วันที่: ____/____/____</p>
                    </div>
                    <div class="signature-box">
                        <p class="signature-label">ผู้ตรวจสอบ</p>
                        <div class="signature-line"></div>
                        <p class="signature-name">ชื่อ: _________________</p>
                        <p class="signature-date">วันที่: ____/____/____</p>
                    </div>
                    <div class="signature-box">
                        <p class="signature-label">ผู้อนุมัติ</p>
                        <div class="signature-line"></div>
                        <p class="signature-name">ชื่อ: _________________</p>
                        <p class="signature-date">วันที่: ____/____/____</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<style>
/* ===== BASE STYLES ===== */
* {
    box-sizing: border-box;
}

.print-container {
    font-family: 'Sarabun', 'Prompt', 'Noto Sans Thai', sans-serif;
}

/* ===== SCREEN STYLES ===== */
@media screen {
    .print-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 2rem;
    }
    
    .print-content {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto;
        background: white;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
        border-radius: 8px;
        overflow: hidden;
        padding: 20mm 15mm;
    }
}

/* ===== HEADER STYLES ===== */
.header-section {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 3px solid #2563eb;
}

.header-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
}

.header-left {
    display: flex;
    gap: 20px;
    flex: 1;
}

.recipe-image-container {
    flex-shrink: 0;
}

.recipe-main-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    border: 3px solid #e5e7eb;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.recipe-image-placeholder {
    width: 100px;
    height: 100px;
    background: #f3f4f6;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    border: 3px dashed #d1d5db;
}

.recipe-title-info {
    flex: 1;
}

.recipe-title {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
    line-height: 1.2;
}

.recipe-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin: 0 0 8px 0;
}

.recipe-description {
    font-size: 13px;
    color: #4b5563;
    margin: 0;
    line-height: 1.4;
}

.header-right {
    text-align: right;
}

.doc-info {
    background: #f8fafc;
    padding: 10px 15px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.doc-number {
    font-size: 16px;
    font-weight: 700;
    color: #2563eb;
    margin: 0 0 2px 0;
}

.doc-date {
    font-size: 12px;
    color: #64748b;
    margin: 0;
}

/* ===== SUMMARY CARDS ===== */
.summary-section {
    margin-bottom: 20px;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 12px;
}

.summary-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 10px;
    border: 1px solid;
}

.yield-card {
    background: #f0f9ff;
    border-color: #bae6fd;
}

.cost-card {
    background: #fef2f2;
    border-color: #fecaca;
}

.unit-cost-card {
    background: #fff7ed;
    border-color: #fed7aa;
}

.price-card {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.card-icon {
    font-size: 24px;
}

.card-content {
    flex: 1;
}

.card-label {
    font-size: 11px;
    color: #64748b;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-value {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.profit-banner {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    font-size: 14px;
}

.profit-divider {
    opacity: 0.5;
}

.profit-item strong {
    font-size: 16px;
}

/* ===== MAIN CONTENT ===== */
.main-content {
    display: grid;
    grid-template-columns: 45% 55%;
    gap: 20px;
}

.section-title {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid;
    display: flex;
    align-items: center;
    gap: 8px;
}

.title-icon {
    font-size: 18px;
}

.title-count {
    font-size: 12px;
    font-weight: 400;
    color: #6b7280;
}

.ingredients-title {
    color: #2563eb;
    border-color: #2563eb;
}

.cost-title {
    color: #dc2626;
    border-color: #dc2626;
}

.steps-title {
    color: #16a34a;
    border-color: #16a34a;
}

/* ===== INGREDIENTS TABLE ===== */
.ingredients-section {
    margin-bottom: 20px;
}

.ingredients-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.ingredients-table th {
    background: #2563eb;
    color: white;
    padding: 8px 10px;
    text-align: left;
    font-weight: 600;
}

.ingredients-table th.col-num {
    width: 35px;
    text-align: center;
}

.ingredients-table th.col-qty {
    width: 90px;
    text-align: right;
}

.ingredients-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #e5e7eb;
}

.ingredients-table td.col-num {
    text-align: center;
    color: #6b7280;
    font-weight: 500;
}

.ingredients-table td.col-name {
    font-weight: 500;
}

.ingredients-table td.col-qty {
    text-align: right;
    color: #4b5563;
}

.ingredients-table tr:nth-child(even) {
    background: #f9fafb;
}

/* ===== COST TABLE ===== */
.cost-section {
    background: #fef2f2;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid #fecaca;
}

.cost-table {
    width: 100%;
    font-size: 13px;
}

.cost-table td {
    padding: 6px 0;
}

.cost-table .cost-value {
    text-align: right;
    font-weight: 500;
}

.cost-table .total-row {
    border-top: 2px solid #dc2626;
    font-weight: 700;
}

.cost-table .total-row td {
    padding-top: 10px;
}

.cost-table .total-value {
    color: #dc2626;
    font-size: 16px;
}

/* ===== STEPS ===== */
.steps-section {
    height: 100%;
}

.steps-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.step-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.step-number {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.step-content {
    flex: 1;
    padding-top: 4px;
}

.step-instruction {
    font-size: 13px;
    line-height: 1.5;
    color: #374151;
    margin: 0;
}

.step-image {
    margin-top: 8px;
    width: 150px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e5e7eb;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* ===== FOOTER ===== */
.footer-section {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 2px solid #e5e7eb;
}

.signature-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.signature-box {
    text-align: center;
    padding: 15px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.signature-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 20px 0;
}

.signature-line {
    border-bottom: 1px solid #9ca3af;
    margin-bottom: 10px;
    height: 30px;
}

.signature-name, .signature-date {
    font-size: 11px;
    color: #6b7280;
    margin: 4px 0;
}

/* ===== PRINT STYLES ===== */
@media print {
    .no-print {
        display: none !important;
    }
    
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    html, body {
        margin: 0;
        padding: 0;
    }
    
    .print-container {
        background: none;
        padding: 0;
    }
    
    .print-content {
        width: 100%;
        padding: 0;
        margin: 0;
        box-shadow: none;
        border-radius: 0;
    }
    
    @page {
        size: A4 portrait;
        margin: 12mm 10mm;
    }
    
    .header-section,
    .summary-section,
    .ingredients-section,
    .cost-section,
    .steps-section,
    .footer-section {
        page-break-inside: avoid;
    }
    
    .step-item {
        page-break-inside: avoid;
    }
    
    /* Adjust sizes for print */
    .recipe-main-image {
        width: 80px;
        height: 80px;
    }
    
    .recipe-image-placeholder {
        width: 80px;
        height: 80px;
        font-size: 32px;
    }
    
    .recipe-title {
        font-size: 24px;
    }
    
    .summary-cards {
        gap: 8px;
    }
    
    .summary-card {
        padding: 10px;
    }
    
    .card-value {
        font-size: 16px;
    }
    
    .main-content {
        gap: 15px;
    }
    
    .step-image {
        width: 120px;
        height: 100px;
    }
    
    /* Print colors */
    .summary-card.yield-card {
        background: #f0f9ff !important;
    }
    
    .summary-card.cost-card {
        background: #fef2f2 !important;
    }
    
    .summary-card.unit-cost-card {
        background: #fff7ed !important;
    }
    
    .summary-card.price-card {
        background: #f0fdf4 !important;
    }
    
    .profit-banner {
        background: #22c55e !important;
    }
    
    .ingredients-table th {
        background: #2563eb !important;
    }
    
    .cost-section {
        background: #fef2f2 !important;
    }
    
    .step-number {
        background: #22c55e !important;
    }
    
    .signature-box {
        background: #f9fafb !important;
    }
}
</style>
