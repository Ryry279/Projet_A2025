document.addEventListener('DOMContentLoaded', function() {

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const hrefAttribute = this.getAttribute('href');
            if (hrefAttribute && hrefAttribute.length > 1) { // Ensure it's not just "#"
                const targetElement = document.querySelector(hrefAttribute);
                if (targetElement) {
                    e.preventDefault();
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Active Nav Link Styling (basic example based on current URL)
    const navLinks = document.querySelectorAll('header nav ul li a');
    const currentPath = window.location.pathname;
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath || 
            (currentPath === '/' && link.getAttribute('href') === '/index.php') || // Handle index
            (currentPath.endsWith('/') && link.getAttribute('href') === currentPath.slice(0,-1))) { // Handle trailing slash
            link.classList.add('active');
        }
    });


    // Reveal elements on scroll
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    // Optional: unobserve after revealing to save resources
                    // observer.unobserve(entry.target); 
                } else {
                    // Optional: remove 'visible' to re-trigger animation if element scrolls out and back in
                    // entry.target.classList.remove('visible'); 
                }
            });
        }, { threshold: 0.1 }); // Adjust threshold (0.1 means 10% of element is visible)

        revealElements.forEach(el => {
            revealObserver.observe(el);
        });
    }


    // Favorite button AJAX
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const courseId = this.dataset.courseId;
            const isCurrentlyFavorited = this.classList.contains('favorited');
            const iconElement = this; // The button itself is the icon

            // Optimistically update UI
            iconElement.classList.toggle('favorited');
            iconElement.textContent = iconElement.classList.contains('favorited') ? '★' : '☆';
            iconElement.title = iconElement.classList.contains('favorited') ? 'Retirer des favoris' : 'Ajouter aux favoris';


            fetch('/toggle_favorite.php', { // Ensure this path is correct from your web root
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    // 'X-CSRF-TOKEN': 'your_csrf_token_here' // Important for security in production
                },
                body: JSON.stringify({ course_id: courseId }) // Server will check current state
            })
            .then(response => {
                if (!response.ok) {
                    // If server returns an error status, try to parse JSON for error message
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update based on server response (though already done optimistically)
                    if (data.action === 'added') {
                        iconElement.classList.add('favorited');
                        iconElement.textContent = '★';
                        iconElement.title = 'Retirer des favoris';
                    } else if (data.action === 'removed') {
                        iconElement.classList.remove('favorited');
                        iconElement.textContent = '☆';
                        iconElement.title = 'Ajouter aux favoris';
                    }
                    // Optionally, display a success message or update a favorites counter
                } else {
                    // Revert optimistic update if server indicates failure
                    iconElement.classList.toggle('favorited'); // Toggle back
                    iconElement.textContent = iconElement.classList.contains('favorited') ? '★' : '☆';
                    iconElement.title = iconElement.classList.contains('favorited') ? 'Retirer des favoris' : 'Ajouter aux favoris';
                    
                    alert('Erreur: ' + (data.message || 'Impossible de mettre à jour les favoris.'));
                    if (data.reason === 'not_logged_in') {
                        // Consider redirecting or showing a login modal
                        // window.location.href = '/login.php'; 
                    }
                }
            })
            .catch(error => {
                // Revert optimistic update on network or parse error
                iconElement.classList.toggle('favorited'); // Toggle back
                iconElement.textContent = iconElement.classList.contains('favorited') ? '★' : '☆';
                iconElement.title = iconElement.classList.contains('favorited') ? 'Retirer des favoris' : 'Ajouter aux favoris';

                console.error('Error toggling favorite:', error);
                let errorMessage = 'Une erreur réseau est survenue.';
                if (error && error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            });
        });
    });

    // Quiz submission (basic example)
    const quizForm = document.getElementById('quizForm');
    if (quizForm) {
        quizForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const quizResultDiv = document.getElementById('quizResult');
            quizResultDiv.innerHTML = '<p>Traitement en cours...</p>';

            fetch('/submit_quiz.php', { // Ensure this path is correct
                method: 'POST',
                body: formData
                // Add CSRF token if implemented
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let resultHTML = `<h4>Résultats du Quiz</h4><p>Votre score: <strong>${data.score}%</strong></p>`;
                    if(data.details) {
                        resultHTML += '<ul>';
                        data.details.forEach(detail => {
                            resultHTML += `<li>Question: "${detail.question}" - Votre réponse: ${detail.correct ? 'Correcte' : 'Incorrecte'}</li>`;
                        });
                        resultHTML += '</ul>';
                    }
                    quizResultDiv.innerHTML = resultHTML;
                    quizForm.style.display = 'none'; // Hide form after submission
                } else {
                    quizResultDiv.innerHTML = `<p class="error">Erreur lors de la soumission: ${data.message || 'Veuillez réessayer.'}</p>`;
                }
            })
            .catch(error => {
                quizResultDiv.innerHTML = `<p class="error">Erreur réseau lors de la soumission du quiz.</p>`;
                console.error('Quiz submission error:', error);
            });
        });
    }

    // Newsletter Subscription AJAX
    const newsletterForm = document.querySelector('footer form[action*="subscribe_newsletter.php"]');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = this.querySelector('input[name="newsletter_email"]');
            const messageContainer = document.querySelector('.newsletter-message') || document.createElement('p');
            if (!document.querySelector('.newsletter-message')) {
                messageContainer.classList.add('newsletter-message');
                this.parentNode.insertBefore(messageContainer, this.nextSibling);
            }
            messageContainer.style.marginTop = '10px';


            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                messageContainer.textContent = data.message;
                if (data.success) {
                    messageContainer.style.color = 'green';
                    emailInput.value = ''; // Clear input on success
                } else {
                    messageContainer.style.color = 'red';
                }
            })
            .catch(error => {
                messageContainer.textContent = 'Erreur lors de l\'inscription. Veuillez réessayer.';
                messageContainer.style.color = 'red';
                console.error('Newsletter subscription error:', error);
            });
        });
    }

});