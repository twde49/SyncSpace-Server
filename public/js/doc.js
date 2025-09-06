document.addEventListener("DOMContentLoaded", () => {
	const endpoints = document.querySelectorAll(".endpoint-header");
	endpoints.forEach((header) => {
		header.addEventListener("click", () => {
			const content = header.nextElementSibling;
			content.style.display =
				content.style.display === "block" ? "none" : "block";
		});
	});

	const copyButtons = document.querySelectorAll(".copy-btn");
	copyButtons.forEach((button) => {
		button.addEventListener("click", (e) => {
			e.stopPropagation();
			const pre = button.parentElement;
			const code = pre.querySelector("code").innerText;
			navigator.clipboard.writeText(code).then(() => {
				button.innerText = "Copié !";
				setTimeout(() => {
					button.innerText = "Copier";
				}, 2000);
			});
		});
	});

	const menuToggle = document.getElementById("menu-toggle");
	const sidebar = document.getElementById("sidebar");
	menuToggle.addEventListener("click", () => {
		sidebar.classList.toggle("active");
	});

	const categoryHeaders = document.querySelectorAll(".category-header");

	// Toggle subcategory visibility
	categoryHeaders.forEach((header) => {
		header.addEventListener("click", () => {
			const targetId = header.getAttribute("data-target");
			const subcategory = document.getElementById(targetId);
			const isActive = header.classList.contains("active");

			// Close all other subcategories first
			document
				.querySelectorAll(".category-header.active")
				.forEach((activeHeader) => {
					if (activeHeader !== header) {
						activeHeader.classList.remove("active");
						const activeTarget = document.getElementById(
							activeHeader.getAttribute("data-target"),
						);
						activeTarget.classList.remove("active");
					}
				});

			// Toggle active class
			header.classList.toggle("active");
			subcategory.classList.toggle("active");
		});
	});

	// Expand the current category based on URL hash
	function expandCurrentCategory() {
		const hash = window.location.hash;
		if (hash) {
			document.querySelectorAll(".subcategory a").forEach((link) => {
				if (link.getAttribute("href") === hash) {
					const subcategory = link.closest(".subcategory");
					const header = subcategory.previousElementSibling;
					header.classList.add("active");
					subcategory.classList.add("active");
				}
			});
		}
	}

	// Expand first category by default if no hash or expand relevant category
	if (!window.location.hash) {
		const firstHeader = document.querySelector(".category-header");
		if (firstHeader) {
			firstHeader.classList.add("active");
			const targetId = firstHeader.getAttribute("data-target");
			const subcategory = document.getElementById(targetId);
			subcategory.classList.add("active");
		}
	} else {
		expandCurrentCategory();

		// Scroll to the target element with a small delay to ensure proper positioning
		setTimeout(() => {
			const targetElement = document.querySelector(window.location.hash);
			if (targetElement) {
				const content = document.querySelector(".content");
				content.scrollTo({
					top: targetElement.offsetTop - 80,
					behavior: "smooth",
				});
			}
		}, 300);
	}

	// Re-check when hash changes
	window.addEventListener("hashchange", expandCurrentCategory);

	// Close sidebar when clicking on a link (mobile)
	const sidebarLinks = document.querySelectorAll(".sidebar a");
	sidebarLinks.forEach((link) => {
		link.addEventListener("click", () => {
			if (window.innerWidth <= 768) {
				sidebar.classList.remove("active");
			}
		});
	});

	const backToTopButton = document.getElementById("back-to-top");
	const content = document.querySelector(".content");

	content.addEventListener("scroll", () => {
		if (content.scrollTop > 300) {
			backToTopButton.classList.add("visible");
		} else {
			backToTopButton.classList.remove("visible");
		}
	});

	backToTopButton.addEventListener("click", (e) => {
		e.preventDefault();
		content.scrollTo({
			top: 0,
			behavior: "smooth",
		});
	});

	document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
		anchor.addEventListener("click", function (e) {
			e.preventDefault();

			const targetId = this.getAttribute("href");
			if (targetId === "#") return;

			const targetElement = document.querySelector(targetId);
			if (targetElement) {
				targetElement.classList.add("highlight-section");
				setTimeout(() => {
					targetElement.classList.remove("highlight-section");
				}, 1500);

				content.scrollTo({
					top: targetElement.offsetTop - 30,
					behavior: "smooth",
				});
			}
		});
	});

	if (window.location.hash) {
		const targetElement = document.querySelector(window.location.hash);
		if (targetElement) {
			setTimeout(() => {
				content.scrollTo({
					top: targetElement.offsetTop - 30,
					behavior: "smooth",
				});

				targetElement.classList.add("highlight-section");
				setTimeout(() => {
					targetElement.classList.remove("highlight-section");
				}, 1500);
			}, 100);
		}
	}
});
