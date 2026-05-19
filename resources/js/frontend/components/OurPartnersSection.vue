<template>
    <section class="relative mt-10 overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-8 mx-auto h-40 max-w-3xl rounded-full bg-[radial-gradient(circle,_rgba(162,95,136,0.16)_0%,_rgba(162,95,136,0.08)_35%,_transparent_72%)] blur-3xl"></div>

        <div class="relative mx-auto max-w-[1200px] rounded-[32px] border border-[#F1E6ED] bg-white px-5 py-10 shadow-[0_20px_50px_rgba(17,24,39,0.06)] sm:px-8 sm:py-12 lg:px-10">
            <div class="mx-auto max-w-3xl text-center">
                <p class="text-[11px] font-semibold uppercase tracking-[0.3em] text-[#A25F88]">OUR PARTNERS</p>
                <h2 class="mt-3 text-[2rem] font-bold tracking-[-0.05em] text-[#111827] sm:text-[2.75rem]">
                    Trusted by leading companies
                </h2>
                <p class="mt-3 text-sm leading-7 text-[#6B7280] sm:text-[15px]">
                    Secure payments and trusted commerce infrastructure for a cleaner checkout experience.
                </p>
            </div>

            <div class="partner-marquee mt-10 sm:mt-12">
                <div class="partner-track">
                    <article
                        v-for="partner in repeatedPartners"
                        :key="partner.key"
                        class="partner-circle-card"
                    >
                        <div class="partner-circle">
                            <img
                                :src="partner.logo"
                                :alt="partner.name"
                                class="max-h-12 w-auto max-w-[92px] object-contain sm:max-h-14 sm:max-w-[108px]"
                            >
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
const partners = [
    { name: 'ABA Bank', logo: '/aba.png' },
    { name: 'ACLEDA', logo: '/ac.png' },
    { name: 'Canadia Bank', logo: '/canadia.png' },
    { name: 'Wing Bank', logo: '/wing.png' },
    { name: 'PayPal', logo: '/paypal.png' },
    { name: 'Cash Payment', logo: '/cash.jpg' },
];

const repeatedPartners = [...partners, ...partners].map((partner, index) => ({
    ...partner,
    key: `${partner.name}-${index}`,
}));
</script>

<style scoped>
.partner-marquee {
    overflow: hidden;
    mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
    -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
}

.partner-track {
    display: flex;
    width: max-content;
    align-items: center;
    gap: 1.25rem;
    animation: partner-scroll 26s linear infinite;
    will-change: transform;
}

.partner-circle-card {
    flex: 0 0 auto;
    padding: 0.25rem;
}

.partner-circle {
    display: flex;
    height: 6.75rem;
    width: 6.75rem;
    align-items: center;
    justify-content: center;
    border: 1px solid #f1e6ed;
    border-radius: 9999px;
    background: #ffffff;
    box-shadow: 0 12px 28px rgba(17, 24, 39, 0.08);
    transition: transform 200ms ease, box-shadow 200ms ease;
}

.partner-circle-card:hover .partner-circle {
    transform: scale(1.05);
    box-shadow: 0 18px 38px rgba(17, 24, 39, 0.14);
}

.partner-marquee:hover .partner-track {
    animation-play-state: paused;
}

@keyframes partner-scroll {
    from {
        transform: translateX(0);
    }

    to {
        transform: translateX(calc(-50% - 0.625rem));
    }
}

@media (min-width: 640px) {
    .partner-track {
        gap: 1.75rem;
    }

    .partner-circle {
        height: 8rem;
        width: 8rem;
    }
}

@media (max-width: 639px) {
    .partner-marquee {
        mask-image: none;
        -webkit-mask-image: none;
    }

    .partner-track {
        gap: 1rem;
        animation-duration: 20s;
    }
}
</style>
