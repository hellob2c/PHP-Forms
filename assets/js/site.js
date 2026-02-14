(() => {
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));

  // Year
  const y = new Date().getFullYear();
  const yearEl = $("#year");
  if (yearEl) yearEl.textContent = String(y);

  // Mobile nav
  const mobileBtn = $("#mobileBtn");
  const mobileNav = $("#mobileNav");
  if (mobileBtn && mobileNav) {
    mobileBtn.addEventListener("click", () => {
      mobileNav.classList.toggle("hidden");
    });
    $$("#mobileNav a").forEach(a => a.addEventListener("click", () => mobileNav.classList.add("hidden")));
  }

  // Scrollspy active nav
  const sections = ["home","services","team","why","contact"].map(id => document.getElementById(id)).filter(Boolean);
  const navLinks = $$(".navlink");

  const setActive = () => {
    const y = window.scrollY + 110;
    let current = "home";
    for (const s of sections) {
      if (s.offsetTop <= y) current = s.id;
    }
    navLinks.forEach(a => a.classList.toggle("active", a.getAttribute("href") === `#${current}`));
  };
  window.addEventListener("scroll", setActive, { passive: true });
  setActive();

  // Reveal on scroll
  const revealEls = $$(".reveal");
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add("show");
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  revealEls.forEach(el => io.observe(el));

  // Counters (animated)
  const counterEls = $$("[data-counter]");
  const animateCounter = (el) => {
    const target = Number(el.getAttribute("data-counter") || "0");
    const duration = 900;
    const start = performance.now();
    const from = 0;
    const tick = (t) => {
      const p = Math.min(1, (t - start) / duration);
      const val = Math.floor(from + (target - from) * (p * (2 - p))); // easeOutQuad
      el.textContent = String(val);
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  };

  const countersIO = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        counterEls.forEach(animateCounter);
        countersIO.disconnect();
      }
    });
  }, { threshold: 0.35 });

  if (counterEls.length) countersIO.observe(counterEls[0]);

  // Back to top
  const backTop = $("#backTop");
  if (backTop) backTop.addEventListener("click", () => window.scrollTo({ top: 0, behavior: "smooth" }));

  // Carousel
  const track = $("#carouselTrack");
  let slide = 0;
  const slides = track ? Array.from(track.children) : [];
  const renderCarousel = () => {
    if (!track) return;
    track.style.transform = `translateX(${slide * -100}%)`;
  };
  const next = () => { slide = (slide + 1) % slides.length; renderCarousel(); };
  const prev = () => { slide = (slide - 1 + slides.length) % slides.length; renderCarousel(); };

  $$("[data-carousel]").forEach(btn => {
    btn.addEventListener("click", () => {
      btn.getAttribute("data-carousel") === "next" ? next() : prev();
    });
  });
  if (slides.length) setInterval(next, 6500);

  // FAQ accordion
  $$(".faq-q").forEach(btn => {
    btn.addEventListener("click", () => {
      const box = btn.closest(".faq");
      if (!box) return;
      box.classList.toggle("open");
    });
  });

  // Modals
  const openModal = (name) => {
    const el = document.getElementById(`modal-${name}`);
    if (!el) return;
    el.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  };
  const closeModals = () => {
    $$(".modal").forEach(m => m.classList.add("hidden"));
    document.body.style.overflow = "";
  };

  $$("[data-open-modal]").forEach(btn => {
    btn.addEventListener("click", () => openModal(btn.getAttribute("data-open-modal")));
  });
  $$("[data-close-modal]").forEach(btn => {
    btn.addEventListener("click", () => closeModals());
  });
  $$(".modal [data-close-modal]").forEach(a => {
    a.addEventListener("click", () => closeModals());
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModals();
  });

  // Service details modal
  const serviceData = {
    sale: {
      title: "Property Sale, Purchase & Rent",
      subtitle: "End-to-end support for buying, selling and leasing.",
      body: `
        <ul class="list-disc ml-5 space-y-2">
          <li><b>Requirement mapping</b> and shortlisting options that match your budget and location.</li>
          <li><b>Site visits & negotiation guidance</b> with realistic market benchmarks.</li>
          <li><b>Drafting and review</b> of key clauses for offers, token, and rent agreements.</li>
          <li><b>Coordination</b> with the other party for timelines, documents, and execution.</li>
        </ul>
        <p class="mt-4">Want to proceed? Use the Quick Enquiry to share details and documents.</p>
      `
    },
    docs: {
      title: "Property Documentation",
      subtitle: "Drafting, verification and registration-ready documents.",
      body: `
        <ul class="list-disc ml-5 space-y-2">
          <li>Sale Deed, Lease Deed, Rent Agreement, Agreement to Sell.</li>
          <li>GPA/SPA formats (where applicable) and execution checklists.</li>
          <li>Stamp duty and registration guidance, appointment prep.</li>
          <li>Document verification support (basic checks) before signing.</li>
        </ul>
        <p class="mt-4">Upload your draft/papers in the enquiry form for a faster review.</p>
      `
    },
    legal: {
      title: "Legal Due Diligence",
      subtitle: "Reduce risk before you commit money.",
      body: `
        <ul class="list-disc ml-5 space-y-2">
          <li>Ownership chain review and title checks (as available).</li>
          <li>Basic encumbrance / red-flag checklist for decision-making.</li>
          <li>Guidance on what to verify with the concerned authorities.</li>
        </ul>
      `
    },
    value: {
      title: "Property Valuation Support",
      subtitle: "Pricing guidance based on locality benchmarks.",
      body: `
        <ul class="list-disc ml-5 space-y-2">
          <li>Market rate estimation using comparable locality insights.</li>
          <li>Negotiation strategy and fair-offer guidance.</li>
          <li>Checklist of costs: stamp duty, registration, brokerage etc.</li>
        </ul>
      `
    },
    verify: {
      title: "Tenant / Buyer Verification",
      subtitle: "A practical checklist to protect you.",
      body: `
        <ul class="list-disc ml-5 space-y-2">
          <li>Document collection guidance (ID, address, employment, etc.).</li>
          <li>Agreement clauses recommendations (security, lock-in, notice).</li>
          <li>Handover checklist to prevent disputes later.</li>
        </ul>
      `
    }
  };

  const serviceModal = $("#modal-service");
  const serviceTitle = $("#serviceTitle");
  const serviceSubtitle = $("#serviceSubtitle");
  const serviceBody = $("#serviceBody");

  const openService = (key) => {
    const s = serviceData[key];
    if (!s || !serviceModal) return;
    serviceTitle.textContent = s.title;
    serviceSubtitle.textContent = s.subtitle;
    serviceBody.innerHTML = s.body;
    serviceModal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  };

  $$("[data-open-service]").forEach(btn => {
    btn.addEventListener("click", () => openService(btn.getAttribute("data-open-service")));
  });

  // Toast
  const toast = $("#toast");
  const showToast = (msg) => {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.remove("hidden");
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toast.classList.add("hidden"), 2600);
  };

  // Form submit helper
  const submitForm = async (form, alertEl, spinnerEl, endpoint) => {
    if (!form) return;
    const fd = new FormData(form);

    // Simple client-side size guard (10MB)
    const files = fd.getAll("docs[]").filter(Boolean);
    let total = 0;
    files.forEach(f => total += (f.size || 0));
    if (total > 10 * 1024 * 1024) {
      alertEl.className = "rounded-xl p-4 text-sm bg-red-50 text-red-700 border border-red-200";
      alertEl.textContent = "Total attachment size must be under 10MB.";
      alertEl.classList.remove("hidden");
      return;
    }

    spinnerEl?.classList.remove("hidden");
    try {
      const res = await fetch(endpoint, { method: "POST", body: fd });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        throw new Error(data.message || "Something went wrong. Please try again.");
      }
      alertEl.className = "rounded-xl p-4 text-sm bg-emerald-50 text-emerald-800 border border-emerald-200";
      alertEl.textContent = "Thanks! We received your message and will contact you soon.";
      alertEl.classList.remove("hidden");
      form.reset();
      showToast("Submitted successfully ✅");
      return true;
    } catch (err) {
      alertEl.className = "rounded-xl p-4 text-sm bg-red-50 text-red-700 border border-red-200";
      alertEl.textContent = err?.message || "Submission failed.";
      alertEl.classList.remove("hidden");
      showToast("Submission failed ❗");
      return false;
    } finally {
      spinnerEl?.classList.add("hidden");
    }
  };

  // Mini form -> opens enquiry modal after submit
  const miniForm = $("#miniForm");
  if (miniForm) {
    miniForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const ok = await submitForm(
        miniForm,
        (() => { const a = document.createElement("div"); return a; })(),
        null,
        "api/submit.php"
      );
      if (ok) openModal("enquiry");
    });
  }

  const enquiryForm = $("#enquiryForm");
  const enquiryAlert = $("#enquiryAlert");
  const enquirySpinner = $("#enquirySpinner");
  if (enquiryForm) {
    enquiryForm.addEventListener("submit", (e) => {
      e.preventDefault();
      submitForm(enquiryForm, enquiryAlert, enquirySpinner, "api/submit.php")
        .then((ok) => { if (ok) setTimeout(closeModals, 1200); });
    });
  }

  const contactForm = $("#contactForm");
  const contactAlert = $("#contactAlert");
  const contactSpinner = $("#contactSpinner");
  if (contactForm) {
    contactForm.addEventListener("submit", (e) => {
      e.preventDefault();
      submitForm(contactForm, contactAlert, contactSpinner, "api/submit.php");
    });
  }
})();

// Scroll Spy: auto highlight nav based on scroll position
(function () {
  const header = document.getElementById("header");
  const navLinks = Array.from(document.querySelectorAll('a.navlink[href^="#"]'));

  if (!navLinks.length) return;

  const sections = navLinks
    .map(link => {
      const id = link.getAttribute("href").slice(1);
      const sec = document.getElementById(id);
      return sec ? { id, sec } : null;
    })
    .filter(Boolean);

  function setActive(id) {
    navLinks.forEach(a => {
      const isActive = a.getAttribute("href") === `#${id}`;
      a.classList.toggle("active", isActive);
      if (isActive) a.setAttribute("aria-current", "page");
      else a.removeAttribute("aria-current");
    });
  }

  function getHeaderOffset() {
    // sticky header height + small buffer
    const h = header ? header.getBoundingClientRect().height : 0;
    return h + 12;
  }

  function onScroll() {
    const offset = getHeaderOffset();
    const scrollPos = window.scrollY + offset;

    // Find last section whose top <= current scroll position
    let current = sections[0]?.id || "home";

    for (const item of sections) {
      const top = item.sec.offsetTop;
      if (top <= scrollPos) current = item.id;
    }

    setActive(current);
  }

  // Smooth + efficient updates
  let ticking = false;
  window.addEventListener("scroll", () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        onScroll();
        ticking = false;
      });
      ticking = true;
    }
  }, { passive: true });

  window.addEventListener("resize", onScroll);
  document.addEventListener("DOMContentLoaded", onScroll);

  // Also update on click instantly (nice UX)
  navLinks.forEach(a => {
    a.addEventListener("click", () => {
      const id = a.getAttribute("href").slice(1);
      setActive(id);
    });
  });

  // Run once now
  onScroll();
})();
