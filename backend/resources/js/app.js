import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// GSAP
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
gsap.registerPlugin(ScrollTrigger);
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;

// Lucide Icons
import { createIcons, icons } from 'lucide';
window.lucide = { createIcons, icons };

document.addEventListener('DOMContentLoaded', () => {
    // Render Lucide icons
    createIcons({ icons });

    // Page entrance animation: stagger all .gsap-enter elements
    const enterEls = document.querySelectorAll('.gsap-enter');
    if (enterEls.length) {
        gsap.from(enterEls, {
            y: 30,
            opacity: 0,
            duration: 0.5,
            stagger: 0.08,
            ease: 'power2.out',
        });
    }

    // Stat card count-up animation (animates 0 → target).
    document.querySelectorAll('[data-countup]').forEach(el => {
        const target = parseFloat(el.dataset.countup);
        if (isNaN(target)) return;
        const isDecimal = el.dataset.countupDecimal === 'true';
        gsap.to({ val: 0 }, {
            val: target,
            duration: 1.5,
            ease: 'power2.out',
            onUpdate: function () {
                el.textContent = isDecimal
                    ? this.targets()[0].val.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })
                    : Math.floor(this.targets()[0].val).toLocaleString();
            },
        });
    });

    // Scroll-triggered entrance for work cards
    gsap.utils.toArray('.scroll-enter').forEach(el => {
        gsap.from(el, {
            scrollTrigger: {
                trigger: el,
                start: 'top 90%',
            },
            y: 40,
            opacity: 0,
            duration: 0.6,
            ease: 'power2.out',
        });
    });
});
