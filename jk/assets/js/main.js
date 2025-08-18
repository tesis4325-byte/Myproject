(function(){
	'use strict';

	// Reveal on scroll
	var observer = new IntersectionObserver(function(entries){
		entries.forEach(function(entry){
			if(entry.isIntersecting){
				entry.target.classList.add('reveal-visible');
				observer.unobserve(entry.target);
			}
		});
	},{threshold:0.15});

	document.querySelectorAll('.reveal').forEach(function(el){observer.observe(el);});

	// Smooth scroll for internal anchors
	document.addEventListener('click', function(e){
		var target = e.target.closest('a[href^="#"]');
		if(!target) return;
		var id = target.getAttribute('href');
		if(id && id.length > 1){
			e.preventDefault();
			var dest = document.querySelector(id);
			if(dest){ dest.scrollIntoView({behavior:'smooth', block:'start'}); }
		}
	});

	// Bootstrap form validation
	var forms = document.querySelectorAll('.needs-validation');
	Array.prototype.slice.call(forms).forEach(function(form){
		form.addEventListener('submit', function(event){
			if(!form.checkValidity()){
				event.preventDefault();
				event.stopPropagation();
			}
			form.classList.add('was-validated');
		}, false);
	});
})();

