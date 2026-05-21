<template>
    <section v-if="workspace" class="space-y-5">
        <article class="overflow-hidden rounded-[24px] border border-[#E5E7EB] bg-[linear-gradient(135deg,#FFFFFF_0%,#FDFBFC_58%,#F7EEF4_100%)] text-[#111827] shadow-[0_10px_28px_rgba(15,23,42,0.04)]">
            <div class="grid gap-4 px-5 py-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div class="min-w-0 max-w-4xl">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-[#A25F88]">{{ workspace.hero?.eyebrow || 'Overview' }}</p>
                    <h2 class="mt-1.5 text-[1.9rem] font-bold tracking-[-0.045em] text-[#111827]">{{ workspace.hero?.title || 'E-commerce Overview' }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-[#6B7280]">{{ headerDescription }}</p>
                </div>

                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-start lg:justify-end">
                    <label class="flex min-h-[44px] items-center gap-3 rounded-[14px] border border-[#E5E7EB] bg-white px-4 py-2.5 text-sm text-[#111827] shadow-[0_6px_16px_rgba(15,23,42,0.04)]">
                        <span class="font-medium text-[#6B7280]">Date</span>
                        <select v-model="selectedRange" class="bg-transparent pr-6 font-semibold text-[#111827] outline-none">
                            <option v-for="item in rangeOptions" :key="item.value" :value="item.value">{{ item.label }}</option>
                        </select>
                    </label>

                    <button type="button" class="inline-flex min-h-[44px] items-center justify-center rounded-[14px] bg-[#A25F88] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#8E4F76]" @click="$emit('refresh')">
                        Refresh
                    </button>
                </div>
            </div>
        </article>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            <article
                v-for="card in workspace.summary_cards"
                :key="card.label"
                class="relative flex min-h-[136px] flex-col justify-between overflow-hidden rounded-[20px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] transition hover:border-[#D8C0CF]"
                :class="summaryCardClass(card)"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em]" :class="summaryLabelClass(card)">{{ card.label }}</p>
                        <p class="mt-3 text-[1.9rem] font-black tracking-[-0.05em] text-[#111827]">{{ card.value }}</p>
                    </div>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[14px]" :class="summaryIconWrapClass(card)">
                        <svg class="h-[18px] w-[18px]" :class="summaryIconClass(card)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="card.icon" />
                        </svg>
                    </span>
                </div>
                <div class="mt-4">
                    <span class="inline-flex rounded-[10px] px-2.5 py-1 text-[11px] font-semibold" :class="trendBadgeClass(card)">
                        {{ card.trend?.label }}
                    </span>
                </div>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.92fr)]">
            <DashboardLineChart
                :title="workspace.analytics?.primary_chart?.title || 'Sales Over Time'"
                :eyebrow="workspace.analytics?.primary_chart?.eyebrow || 'Sales analytics'"
                :points="salesOverTimePoints"
                :trend="activeDataset.trend"
            />
            <DashboardDonutChart title="Order Status Overview" eyebrow="Orders" :items="activeDataset.order_status" />
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <DashboardCategoryBarChart
                title="Sales by Category"
                eyebrow="Category performance"
                :items="activeDataset.sales_by_category"
                legend-label="Sales"
                bar-color="#A25F88"
            />
            <DashboardCategoryBarChart
                title="Top Selling Products"
                eyebrow="Best sellers"
                :items="activeDataset.top_products"
                legend-label="Units"
                bar-color="#3B82F6"
            />
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.92fr)]">
            <DashboardLineChart
                :title="workspace.analytics?.secondary_chart?.title || 'Revenue Trend'"
                :eyebrow="workspace.analytics?.secondary_chart?.eyebrow || 'Revenue movement'"
                :points="revenueTrendPoints"
                :trend="activeDataset.secondary_trend || activeDataset.trend"
                line-color="#A25F88"
            />
            <DashboardDonutChart title="Payment Method Overview" eyebrow="Payments" :items="activeDataset.payment_methods" />
        </section>

        <section class="grid gap-5 2xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <article class="rounded-[22px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Orders</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Recent Orders</h3>
                    </div>
                    <button type="button" class="rounded-[12px] border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-2 text-xs font-semibold text-[#6B7280] transition hover:border-[#D8C0CF] hover:text-[#A25F88]" @click="navigateTo(workspace.actions?.orders_path)">
                        Open Orders
                    </button>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-[860px] w-full text-left text-sm">
                        <thead class="bg-[#F8FAFC] text-[#6B7280]">
                            <tr>
                                <th class="px-3 py-3 font-semibold">Order ID</th>
                                <th class="px-3 py-3 font-semibold">Customer</th>
                                <th class="px-3 py-3 font-semibold">Product</th>
                                <th class="px-3 py-3 font-semibold">Amount</th>
                                <th class="px-3 py-3 font-semibold">Payment</th>
                                <th class="px-3 py-3 font-semibold">Status</th>
                                <th class="px-3 py-3 font-semibold">Date</th>
                                <th class="px-3 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in workspace.recent_orders" :key="order.id" class="border-t border-[#F1F5F9] transition hover:bg-[#FBFCFE]">
                                <td class="px-3 py-3.5 font-semibold text-[#111827]">{{ order.id }}</td>
                                <td class="px-3 py-3.5 text-[#475569]">{{ order.customer }}</td>
                                <td class="max-w-[220px] px-3 py-3.5 text-[#475569]"><span class="block truncate">{{ order.product }}</span></td>
                                <td class="px-3 py-3.5 font-semibold text-[#111827]">{{ order.amount }}</td>
                                <td class="px-3 py-3.5 text-[#475569]">{{ order.payment }}</td>
                                <td class="px-3 py-3.5">
                                    <span class="rounded-[10px] px-2.5 py-1 text-xs font-semibold" :class="orderStatusClass(order.status)">{{ order.status }}</span>
                                </td>
                                <td class="px-3 py-3.5 text-[#64748B]">{{ order.date }}</td>
                                <td class="px-3 py-3.5 text-right">
                                    <button type="button" class="rounded-[10px] border border-[#E5E7EB] bg-white px-3 py-1.5 text-xs font-semibold text-[#475569] transition hover:border-[#D8C0CF] hover:text-[#A25F88]" @click="navigateTo(workspace.actions?.orders_path)">
                                        View
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="(workspace.recent_orders?.length ?? 0) === 0">
                                <td colspan="8" class="px-3 py-8 text-center text-sm text-[#94A3B8]">No recent orders available.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="rounded-[22px] border border-[#E5E7EB] bg-white p-5 shadow-[0_8px_24px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#6B7280]">Inventory</p>
                        <h3 class="mt-1 text-xl font-bold tracking-[-0.03em] text-[#111827]">Low Stock Products</h3>
                    </div>
                    <button type="button" class="rounded-[12px] border border-[#E5E7EB] bg-[#F8FAFC] px-3.5 py-2 text-xs font-semibold text-[#6B7280] transition hover:border-[#D8C0CF] hover:text-[#A25F88]" @click="navigateTo(workspace.actions?.products_path)">
                        Open Products
                    </button>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-[#F8FAFC] text-[#6B7280]">
                            <tr>
                                <th class="px-3 py-3 font-semibold">Product Name</th>
                                <th class="px-3 py-3 font-semibold">Category</th>
                                <th class="px-3 py-3 font-semibold">Stock</th>
                                <th class="px-3 py-3 font-semibold">Status</th>
                                <th class="px-3 py-3 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in workspace.low_stock_products" :key="`${product.name}-${product.stock}`" class="border-t border-[#F1F5F9] transition hover:bg-[#FBFCFE]">
                                <td class="px-3 py-3.5 font-semibold text-[#111827]">{{ product.name }}</td>
                                <td class="px-3 py-3.5 text-[#475569]">{{ product.category }}</td>
                                <td class="px-3 py-3.5 font-semibold text-[#111827]">{{ product.stock }}</td>
                                <td class="px-3 py-3.5">
                                    <span class="rounded-[10px] px-2.5 py-1 text-xs font-semibold" :class="stockStatusClass(product.status)">{{ product.status === 'Low' ? 'Low Stock' : product.status }}</span>
                                </td>
                                <td class="px-3 py-3.5 text-right">
                                    <button type="button" class="rounded-[10px] border border-[#E5E7EB] bg-white px-3 py-1.5 text-xs font-semibold text-[#475569] transition hover:border-[#D8C0CF] hover:text-[#A25F88]" @click="navigateTo(workspace.actions?.products_path)">
                                        Restock
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="(workspace.low_stock_products?.length ?? 0) === 0">
                                <td colspan="5" class="px-3 py-8 text-center text-sm text-[#94A3B8]">No low stock products right now.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import DashboardCategoryBarChart from './dashboard-workspace/DashboardCategoryBarChart.vue';
import DashboardDonutChart from './dashboard-workspace/DashboardDonutChart.vue';
import DashboardLineChart from './dashboard-workspace/DashboardLineChart.vue';

defineEmits(['refresh']);

const props = defineProps({
    workspace: {
        type: Object,
        required: true,
    },
});

const selectedRange = ref(props.workspace?.selected_range ?? '30days');

watch(
    () => props.workspace?.selected_range,
    (value) => {
        selectedRange.value = value || '30days';
    }
);

const rangeOptions = computed(() => props.workspace?.range_options ?? []);
const activeDataset = computed(() => props.workspace?.datasets?.[selectedRange.value] ?? {
    sales_bars: [],
    revenue_trend: [],
    order_status: [],
    sales_by_category: [],
    top_products: [],
    payment_methods: [],
    trend: { direction: 'up', label: '+0.0%', delta: '+0.0% vs previous period' },
});

const salesOverTimePoints = computed(() => activeDataset.value.sales_bars.map((item) => ({
    label: item.label,
    value: Number(item.value || 0),
})));

const revenueTrendPoints = computed(() => activeDataset.value.revenue_trend.map((item) => ({
    label: item.label,
    value: Number(item.value || 0),
})));

const headerDescription = computed(() => workspaceHeaderDescription(props.workspace));

function orderStatusClass(status) {
    return {
        Completed: 'bg-[#DCFCE7] text-[#15803D]',
        Pending: 'bg-[#FEF3C7] text-[#B45309]',
        Cancelled: 'bg-[#FEE2E2] text-[#DC2626]',
        Processing: 'bg-[#DBEAFE] text-[#2563EB]',
    }[status] ?? 'bg-[#F8FAFC] text-[#64748B]';
}

function stockStatusClass(status) {
    return {
        Low: 'bg-[#FEF3C7] text-[#B45309]',
        Critical: 'bg-[#FEE2E2] text-[#DC2626]',
    }[status] ?? 'bg-[#F8FAFC] text-[#64748B]';
}

function summaryCardClass(card) {
    return {
        'Total Revenue': 'bg-[linear-gradient(180deg,#FDF8FB_0%,#FFFFFF_100%)]',
        'Total Orders': 'bg-[linear-gradient(180deg,#F8FAFF_0%,#FFFFFF_100%)]',
        'Total Customers': 'bg-[linear-gradient(180deg,#F7FCFA_0%,#FFFFFF_100%)]',
        'Total Products': 'bg-[linear-gradient(180deg,#FAFAFF_0%,#FFFFFF_100%)]',
        'Pending Orders': 'bg-[linear-gradient(180deg,#FFF9F3_0%,#FFFFFF_100%)]',
        'Low Stock Products': 'bg-[linear-gradient(180deg,#FFF7F7_0%,#FFFFFF_100%)]',
    }[card.label] ?? 'bg-white';
}

function summaryLabelClass(card) {
    return {
        'Total Revenue': 'text-[#A25F88]',
        'Total Orders': 'text-[#3B82F6]',
        'Total Customers': 'text-[#10B981]',
        'Total Products': 'text-[#8B5CF6]',
        'Pending Orders': 'text-[#F59E0B]',
        'Low Stock Products': 'text-[#EF4444]',
    }[card.label] ?? 'text-[#6B7280]';
}

function summaryIconWrapClass(card) {
    return {
        'Total Revenue': 'bg-[#F6EAF1]',
        'Total Orders': 'bg-[#EFF6FF]',
        'Total Customers': 'bg-[#ECFDF5]',
        'Total Products': 'bg-[#F5F3FF]',
        'Pending Orders': 'bg-[#FFF7ED]',
        'Low Stock Products': 'bg-[#FEF2F2]',
    }[card.label] ?? 'bg-[#F8FAFC]';
}

function summaryIconClass(card) {
    return {
        'Total Revenue': 'text-[#A25F88]',
        'Total Orders': 'text-[#3B82F6]',
        'Total Customers': 'text-[#10B981]',
        'Total Products': 'text-[#8B5CF6]',
        'Pending Orders': 'text-[#F59E0B]',
        'Low Stock Products': 'text-[#EF4444]',
    }[card.label] ?? 'text-[#6B7280]';
}

function trendBadgeClass(card) {
    return String(card.trend?.label || '').startsWith('-')
        ? 'bg-[#FEF2F2] text-[#DC2626]'
        : 'bg-[#ECFDF5] text-[#059669]';
}

function navigateTo(path) {
    if (!path) {
        return;
    }

    window.location.href = path;
}

function workspaceHeaderDescription(workspace) {
    const heroTitle = String(workspace?.hero?.title || '').toLowerCase();

    if (heroTitle.includes('merchant')) {
        return 'Track orders, customers, products, and store performance from one clean merchant summary.';
    }

    return 'Track sales, customers, orders, and inventory from a focused admin summary.';
}
</script>
