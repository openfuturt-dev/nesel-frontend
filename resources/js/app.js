import { guardSubmission } from './submit-guard';

const menuToggle = document.querySelector('#menu-toggle');
const mobileMenu = document.querySelector('#mobile-menu');

menuToggle?.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';

    menuToggle.setAttribute('aria-expanded', String(!isOpen));
    menuToggle.setAttribute('aria-label', isOpen ? 'Ouvrir le menu' : 'Fermer le menu');
    mobileMenu?.classList.toggle('hidden', isOpen);
});

mobileMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
        menuToggle?.setAttribute('aria-expanded', 'false');
        menuToggle?.setAttribute('aria-label', 'Ouvrir le menu');
    });
});

document.querySelectorAll('.city-choice').forEach((button) => {
    button.addEventListener('click', () => {
        const citySelect = document.querySelector('#city-select');

        if (citySelect instanceof HTMLSelectElement) {
            citySelect.value = button.dataset.city ?? '';
        }

        document.querySelector('#contact')?.scrollIntoView({ behavior: 'smooth' });
    });
});

const callbackForm = document.querySelector('#callback-form');

if (callbackForm instanceof HTMLFormElement) {
    guardSubmission(callbackForm);
}

const formSuccess = document.querySelector('#form-success');

if (formSuccess) {
    window.setTimeout(() => {
        formSuccess?.classList.add('hidden');
    }, 7000);
}

const revealObserver = new IntersectionObserver(
    (entries, observer) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.12 },
);

document.querySelectorAll('[data-reveal]').forEach((element) => revealObserver.observe(element));
