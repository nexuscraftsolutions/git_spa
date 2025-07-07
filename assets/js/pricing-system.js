// Global Pricing System
class PricingSystem {
    constructor() {
        this.currentPricingType = 'in_city'; // Default
        this.currentTherapistData = {};
        this.init();
    }

    init() {
        this.loadSavedPreference();
        this.showLocationPopupIfNeeded();
        this.initializeEventListeners();
        this.updateAllPrices();
    }

    loadSavedPreference() {
        const saved = localStorage.getItem('pricingPreference');
        if (saved) {
            this.currentPricingType = saved;
        }
    }

    showLocationPopupIfNeeded() {
        // Show popup only if user hasn't made a selection before
        const hasSelectedBefore = localStorage.getItem('pricingPreference');
        if (!hasSelectedBefore) {
            this.showLocationPopup();
        }
    }

    showLocationPopup() {
        const popupHTML = `
            <div id="locationPricingPopup" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-gradient-primary text-white">
                            <h5 class="modal-title">
                                <i class="bi bi-geo-alt me-2"></i>Select Your Location
                            </h5>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div class="mb-4">
                                <i class="bi bi-map display-4 text-primary mb-3"></i>
                                <h4>Choose Your Pricing Zone</h4>
                                <p class="text-muted">This helps us show you the most accurate pricing for our services.</p>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <button class="btn btn-outline-primary btn-lg w-100 location-btn" data-type="in_city">
                                        <i class="bi bi-building me-2"></i>
                                        <div>
                                            <strong>In-City</strong><br>
                                            <small>Delhi & nearby areas</small>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-outline-primary btn-lg w-100 location-btn" data-type="out_city">
                                        <i class="bi bi-geo-alt me-2"></i>
                                        <div>
                                            <strong>Out-City</strong><br>
                                            <small>Outside Delhi</small>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    You can change this anytime using the location buttons on the website.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button class="btn btn-secondary" onclick="pricingSystem.closeLocationPopup()">
                                Skip (Use In-City)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', popupHTML);

        // Add event listeners for location buttons
        document.querySelectorAll('.location-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const type = e.currentTarget.dataset.type;
                this.setPricingType(type);
                this.closeLocationPopup();
            });
        });
    }

    closeLocationPopup() {
        const popup = document.getElementById('locationPricingPopup');
        if (popup) {
            popup.remove();
        }
        // Set default if no selection made
        if (!localStorage.getItem('pricingPreference')) {
            this.setPricingType('in_city');
        }
    }

    setPricingType(type) {
        this.currentPricingType = type;
        localStorage.setItem('pricingPreference', type);
        this.updateAllPrices();
        this.updateLocationButtons();
    }

    updateLocationButtons() {
        // Update all location toggle buttons
        document.querySelectorAll('.location-toggle-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.type === this.currentPricingType) {
                btn.classList.add('active');
            }
        });
    }

    updateAllPrices() {
        // Update all dynamic prices on the page
        document.querySelectorAll('.dynamic-price').forEach(priceElement => {
            const inCityPrice = parseFloat(priceElement.dataset.inCity || 0);
            const outCityPrice = parseFloat(priceElement.dataset.outCity || 0);
            const price = this.currentPricingType === 'in_city' ? inCityPrice : outCityPrice;
            
            priceElement.textContent = '₹' + new Intl.NumberFormat('en-IN').format(price);
        });

        // Update booking modal if open
        this.updateBookingPricing();
    }

    updateBookingPricing() {
        const bookingModal = document.getElementById('bookingModal');
        if (!bookingModal || !bookingModal.classList.contains('show')) return;

        // Update pricing type radio buttons
        const pricingRadio = document.querySelector(`input[name="bookingPricingType"][value="${this.currentPricingType}"]`);
        if (pricingRadio) {
            pricingRadio.checked = true;
        }

        // Recalculate booking prices
        this.calculateBookingPrice();
    }

    calculateBookingPrice() {
        if (!this.currentTherapistData) return;

        const time = document.getElementById('bookingTimeSelect')?.value;
        
        // Get base price based on current pricing type
        const basePrice = this.currentPricingType === 'in_city' ? 
            parseFloat(this.currentTherapistData.in_city_price || 0) : 
            parseFloat(this.currentTherapistData.out_city_price || 0);
        
        // Calculate night fee
        const nightFee = (time && this.isNightTime(time) && this.currentTherapistData.night_fee_enabled) ? 1500 : 0;
        const totalPrice = basePrice + nightFee;
        
        // Update display elements
        this.updatePriceDisplay(basePrice, nightFee, totalPrice);
    }

    updatePriceDisplay(basePrice, nightFee, totalPrice) {
        const baseAmountDisplay = document.getElementById('baseAmountDisplay');
        const nightFeeDisplay = document.getElementById('nightFeeDisplay');
        const displayAmount = document.getElementById('displayAmount');
        const bookingAmount = document.getElementById('bookingAmount');
        const pricingInfo = document.getElementById('pricingInfo');

        if (baseAmountDisplay) baseAmountDisplay.textContent = '₹' + new Intl.NumberFormat('en-IN').format(basePrice);
        if (nightFeeDisplay) nightFeeDisplay.textContent = '₹' + new Intl.NumberFormat('en-IN').format(nightFee);
        if (displayAmount) displayAmount.textContent = '₹' + new Intl.NumberFormat('en-IN').format(totalPrice);
        if (bookingAmount) bookingAmount.value = totalPrice;
        
        if (pricingInfo) {
            const locationText = this.currentPricingType === 'in_city' ? 'In-City' : 'Out-City';
            const nightText = nightFee > 0 ? ' + Night Fee (₹1500)' : '';
            pricingInfo.innerHTML = `<i class="bi bi-info-circle me-1"></i>${locationText} pricing${nightText}`;
        }
    }

    isNightTime(time) {
        if (!time) return false;
        const hour = parseInt(time.split(':')[0]);
        return hour >= 22 || hour < 6;
    }

    setTherapistData(therapistData) {
        this.currentTherapistData = therapistData;
        this.calculateBookingPrice();
    }

    initializeEventListeners() {
        // Listen for location toggle button clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.location-toggle-btn')) {
                const btn = e.target.closest('.location-toggle-btn');
                const type = btn.dataset.type;
                this.setPricingType(type);
            }
        });

        // Listen for booking pricing type changes
        document.addEventListener('change', (e) => {
            if (e.target.name === 'bookingPricingType') {
                this.setPricingType(e.target.value);
            }
        });

        // Listen for time changes in booking form
        document.addEventListener('change', (e) => {
            if (e.target.id === 'bookingTimeSelect') {
                this.calculateBookingPrice();
            }
        });
    }
}

// Initialize global pricing system
let pricingSystem;
document.addEventListener('DOMContentLoaded', function() {
    pricingSystem = new PricingSystem();
});

// Global functions for backward compatibility
function updateBookingPricing() {
    if (pricingSystem) {
        pricingSystem.calculateBookingPrice();
    }
}

function openBookingModal(therapistId) {
    // Set therapist ID in all forms
    const inquiryTherapistId = document.getElementById('inquiryTherapistId');
    const bookingTherapistId = document.getElementById('bookingTherapistId');
    
    if (inquiryTherapistId) inquiryTherapistId.value = therapistId;
    if (bookingTherapistId) bookingTherapistId.value = therapistId;
    
    // Fetch therapist details
    fetch(`get_therapist_details.php?id=${therapistId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (pricingSystem) {
                    pricingSystem.setTherapistData(data.therapist);
                }
                updateWhatsAppButtons(data.therapist);
            }
        })
        .catch(error => console.error('Error fetching therapist details:', error));
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    modal.show();
}

function updateWhatsAppButtons(therapist) {
    const generalBtn = document.getElementById('whatsappGeneralBtn');
    const bookingBtn = document.getElementById('whatsappBookingBtn');
    
    if (generalBtn) {
        generalBtn.onclick = () => openWhatsAppChat(therapist.name, 'general');
    }
    
    if (bookingBtn) {
        bookingBtn.onclick = () => openWhatsAppChat(therapist.name, 'booking');
    }
}

function openWhatsAppChat(therapistName, type = 'general') {
    let message = '';
    
    if (type === 'booking') {
        message = `Hi! I'm interested in booking a session with ${therapistName}. Could you please provide more information about availability and pricing?`;
    } else {
        message = `Hi! I have some questions about ${therapistName}'s services. Could you please help me?`;
    }
    
    const whatsappUrl = `https://wa.me/917005120041?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}