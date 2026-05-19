<template>
    <div class="space-y-6">
        <section class="admin-card rounded-[30px] px-6 py-6 lg:sticky lg:top-8">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Live preview</p>
                    <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">{{ form.name || 'New product' }}</h3>
                </div>
                <span class="chatgpt-pill rounded-full border px-3 py-1 text-xs uppercase" :class="statusClass(form.status)">
                    {{ selectedStatusLabel() }}
                </span>
            </div>

            <div class="mt-6 space-y-5">
                <div class="rounded-[24px] bg-[linear-gradient(135deg,#1e293b,#0f766e)] px-5 py-5 text-white">
                    <p class="chatgpt-kicker text-[11px] uppercase text-white/60">{{ selectedCategoryName() }}</p>
                    <div class="mt-4 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-3xl font-semibold">{{ effectivePriceLabel() }}</p>
                            <p v-if="compareAtPriceLabel()" class="mt-2 text-sm text-white/70 line-through">{{ compareAtPriceLabel() }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/20 px-3 py-2 text-right">
                            <p class="chatgpt-kicker text-[11px] uppercase text-white/55">{{ selectedTypeLabel() }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ inventoryLabel() }}</p>
                        </div>
                    </div>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4">
                        <dt class="chatgpt-kicker text-[11px] uppercase text-slate-400">SKU</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ form.sku || 'Awaiting SKU' }}</dd>
                    </div>
                    <div class="rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4">
                        <dt class="chatgpt-kicker text-[11px] uppercase text-slate-400">Images</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ form.images.length }} selected</dd>
                    </div>
                    <div class="rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4">
                        <dt class="chatgpt-kicker text-[11px] uppercase text-slate-400">Variants</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ filledVariantCount() }} ready</dd>
                    </div>
                    <div class="rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4">
                        <dt class="chatgpt-kicker text-[11px] uppercase text-slate-400">Description</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-900">{{ descriptionWordCount() }} words</dd>
                    </div>
                </dl>

                <div class="rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-5 py-5">
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Description preview</p>
                    <p class="chatgpt-copy mt-3 text-sm">{{ previewDescription() }}</p>
                </div>
            </div>
        </section>

        <section class="admin-card rounded-[30px] px-6 py-6">
            <div class="flex flex-col gap-4">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Checklist</p>
                    <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Before you save</h3>
                </div>
                <div class="space-y-3">
                    <div v-for="item in formChecklist()" :key="item.label" class="flex items-center justify-between gap-3 rounded-[22px] border border-slate-200/80 bg-[#fbfcff] px-4 py-3">
                        <span class="text-sm font-medium text-slate-700">{{ item.label }}</span>
                        <span class="chatgpt-pill rounded-full px-3 py-1 text-[11px] uppercase" :class="item.ready ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                            {{ item.ready ? 'Ready' : 'Pending' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="admin-card rounded-[30px] px-6 py-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="chatgpt-kicker text-[11px] uppercase text-slate-400">Recent products</p>
                    <h3 class="chatgpt-title mt-2 text-2xl text-slate-950">Latest catalog items</h3>
                </div>
                <button
                    type="button"
                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:-translate-y-0.5 hover:text-slate-950"
                    @click="$emit('scroll-inventory')"
                >
                    See all
                </button>
            </div>

            <div class="mt-6 space-y-3">
                <article v-for="product in products.slice(0, 4)" :key="`recent-${product.id}`" class="flex items-start gap-3 rounded-[24px] border border-slate-200/80 bg-[#fbfcff] px-4 py-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-semibold text-white" :class="themeClass(product.theme)">
                        {{ product.initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ product.name }}</p>
                                <p class="mt-1 truncate text-sm text-slate-500">{{ product.category }}</p>
                            </div>
                            <span class="chatgpt-pill rounded-full border px-2.5 py-1 text-[10px] uppercase" :class="statusClass(product.status)">
                                {{ product.status }}
                            </span>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 text-sm text-slate-500">
                            <span>{{ product.price }}</span>
                            <span>{{ product.updated_at }}</span>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>

<script setup>
const currencyFormatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

defineEmits(['scroll-inventory']);

const props = defineProps({
    dashboard: {
        type: Object,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        required: true,
    },
});

function selectedCategoryName() {
    const category = (props.dashboard.form?.categories ?? []).find((item) => String(item.id) === String(props.form.category_id));

    return category?.name ?? 'Select category';
}

function selectedTypeLabel() {
    const type = (props.dashboard.form?.types ?? []).find((item) => item.value === props.form.type);

    return type?.label ?? 'Type';
}

function selectedStatusLabel() {
    const status = (props.dashboard.form?.statuses ?? []).find((item) => item.value === props.form.status);

    return status?.label ?? 'Status';
}

function filledVariantCount() {
    return props.form.variants.filter((variant) => {
        const hasAttributes = Array.isArray(variant.attributes) && variant.attributes.length > 0;
        const hasPrice = String(variant.price ?? '').trim() !== '';
        const hasStock = String(variant.stock ?? '').trim() !== '';

        return hasAttributes && (hasPrice || hasStock);
    }).length;
}

function effectivePriceLabel() {
    return formatCurrency(props.form.discount_price || props.form.price);
}

function compareAtPriceLabel() {
    return String(props.form.discount_price).trim() !== '' ? formatCurrency(props.form.price) : '';
}

function inventoryLabel() {
    const stock = Number.parseInt(props.form.stock_quantity, 10);

    return Number.isFinite(stock) ? `${stock} units` : 'Inventory pending';
}

function previewDescription() {
    const text = stripHtml(props.form.description).trim();

    return text || 'Rich description text will appear here once you start writing.';
}

function descriptionWordCount() {
    const text = stripHtml(props.form.description).trim();

    return text ? text.split(/\s+/).length : 0;
}

function formChecklist() {
    return [
        { label: 'Product name', ready: Boolean(props.form.name.trim()) },
        { label: 'Category', ready: Boolean(props.form.category_id) },
        { label: 'Description', ready: Boolean(stripHtml(props.form.description).trim()) },
        { label: 'Pricing', ready: Boolean(props.form.price) },
        { label: 'Stock quantity', ready: Boolean(String(props.form.stock_quantity).trim()) },
        { label: 'Images uploaded', ready: props.form.images.length > 0 },
        { label: 'Status selected', ready: Boolean(props.form.status) },
    ];
}

function formatCurrency(value) {
    const amount = Number.parseFloat(value);

    return Number.isFinite(amount) ? currencyFormatter.format(amount) : '$0.00';
}

function stripHtml(value) {
    return String(value ?? '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ');
}

function statusClass(status) {
    return {
        active: 'border-emerald-200 bg-emerald-50 text-emerald-700',
        scheduled: 'border-sky-200 bg-sky-50 text-sky-700',
        draft: 'border-amber-200 bg-amber-50 text-amber-700',
    }[status] ?? 'border-slate-200 bg-slate-50 text-slate-600';
}

function themeClass(theme) {
    return {
        cobalt: 'bg-[linear-gradient(135deg,#3457ff,#6ea8ff)]',
        forest: 'bg-[linear-gradient(135deg,#0f766e,#34d399)]',
        sand: 'bg-[linear-gradient(135deg,#a16207,#fbbf24)]',
        graphite: 'bg-[linear-gradient(135deg,#0f172a,#475569)]',
        midnight: 'bg-[linear-gradient(135deg,#111827,#4338ca)]',
        sky: 'bg-[linear-gradient(135deg,#38bdf8,#bfdbfe)]',
        ink: 'bg-[linear-gradient(135deg,#1e293b,#334155)]',
        plum: 'bg-[linear-gradient(135deg,#7c3aed,#c084fc)]',
        denim: 'bg-[linear-gradient(135deg,#1d4ed8,#60a5fa)]',
        lilac: 'bg-[linear-gradient(135deg,#8b5cf6,#e9d5ff)]',
    }[theme] ?? 'bg-[linear-gradient(135deg,#334155,#94a3b8)]';
}
</script>
