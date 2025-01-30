// Flash mesajlarını otomatik olarak kaldır
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.classList.add('fade');
            setTimeout(() => alert.remove(), 300);
        }, 3000);
    });
});

// Yıldız derecelendirme sistemi
function initRating() {
    const ratingContainers = document.querySelectorAll('.rating-container');
    ratingContainers.forEach(container => {
        const stars = container.querySelectorAll('.star');
        const input = container.querySelector('input[type="hidden"]');
        
        stars.forEach((star, index) => {
            star.addEventListener('mouseover', () => {
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('active');
                }
            });
            
            star.addEventListener('mouseout', () => {
                stars.forEach(s => {
                    if (!s.classList.contains('selected')) {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('click', () => {
                stars.forEach(s => s.classList.remove('selected'));
                for (let i = 0; i <= index; i++) {
                    stars[i].classList.add('selected');
                }
                if (input) {
                    input.value = index + 1;
                }
            });
        });
    });
}

// Platform ekleme/çıkarma işlemleri
function initPlatformManager() {
    const container = document.querySelector('.platform-container');
    if (!container) return;

    const addButton = document.querySelector('.add-platform');
    const template = document.querySelector('#platform-template');
    
    if (addButton && template) {
        addButton.addEventListener('click', () => {
            const clone = template.content.cloneNode(true);
            container.appendChild(clone);
            
            const newRow = container.lastElementChild;
            const removeButton = newRow.querySelector('.remove-platform');
            
            if (removeButton) {
                removeButton.addEventListener('click', () => {
                    newRow.remove();
                });
            }
        });
        
        // Mevcut platform satırları için silme işlevselliği
        document.querySelectorAll('.remove-platform').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.platform-row').remove();
            });
        });
    }
}

// Form doğrulama
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            
            const feedback = field.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = 'Bu alan zorunludur.';
            }
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// IMDB puanı doğrulama
function validateImdbRating(input) {
    const value = parseFloat(input.value);
    if (isNaN(value) || value < 0 || value > 10) {
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = 'IMDB puanı 0 ile 10 arasında olmalıdır.';
        }
        return false;
    }
    input.classList.remove('is-invalid');
    return true;
}

// Yayın yılı doğrulama
function validateReleaseYear(input) {
    const value = parseInt(input.value);
    const currentYear = new Date().getFullYear();
    
    if (isNaN(value) || value < 1900 || value > currentYear) {
        input.classList.add('is-invalid');
        const feedback = input.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.textContent = `Yayın yılı 1900 ile ${currentYear} arasında olmalıdır.`;
        }
        return false;
    }
    input.classList.remove('is-invalid');
    return true;
}

// Modal işlemleri
function initModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!validateForm(form)) {
                    e.preventDefault();
                }
            });
        }
    });
}

// Sayfa yüklendiğinde gerekli işlevleri başlat
document.addEventListener('DOMContentLoaded', function() {
    initRating();
    initPlatformManager();
    initModals();
    
    // IMDB puanı doğrulama
    const imdbInputs = document.querySelectorAll('input[name="imdb_rating"]');
    imdbInputs.forEach(input => {
        input.addEventListener('blur', () => validateImdbRating(input));
    });
    
    // Yayın yılı doğrulama
    const yearInputs = document.querySelectorAll('input[name="release_year"]');
    yearInputs.forEach(input => {
        input.addEventListener('blur', () => validateReleaseYear(input));
    });
});

// Silme işlemleri için onay
function confirmDelete(event, type) {
    if (!confirm(`Bu ${type}'i silmek istediğinizden emin misiniz?`)) {
        event.preventDefault();
    }
}

// Arama formu filtreleme
function initSearchForm() {
    const form = document.querySelector('.search-form');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            form.submit();
        });
    });
}

// Sayfa yüklendiğinde arama formunu başlat
document.addEventListener('DOMContentLoaded', function() {
    initSearchForm();
}); 