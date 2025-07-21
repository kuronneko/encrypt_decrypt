// Modal functionality for User Management
import { fetchRoute, showNotification } from "../configuracion.js";

class UserModal {
    constructor() {
        this.modal = $('#addUserModal');
        this.form = $('#addUserForm');
        this.init();
    }

    init() {
        this.bindEvents();
        this.initRandomData();
    }

    initRandomData() {
        // Sample data for random generation
        this.randomData = {
            firstNames: ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Chris', 'Jessica', 'Daniel', 'Ashley', 'Matthew', 'Amanda', 'James', 'Lisa', 'Robert', 'Maria', 'Alexander', 'Jennifer', 'William', 'Elizabeth'],
            lastNames: ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Taylor', 'Thomas', 'Jackson', 'White', 'Harris'],
            locationCombos: [
                { city: 'New York', state: 'NY', country: 'United States', postalFormat: '10###' },
                { city: 'Los Angeles', state: 'CA', country: 'United States', postalFormat: '90###' },
                { city: 'Chicago', state: 'IL', country: 'United States', postalFormat: '606##' },
                { city: 'Houston', state: 'TX', country: 'United States', postalFormat: '77###' },
                { city: 'Phoenix', state: 'AZ', country: 'United States', postalFormat: '85###' },
                { city: 'Toronto', state: 'ON', country: 'Canada', postalFormat: 'M#A #B#' },
                { city: 'Vancouver', state: 'BC', country: 'Canada', postalFormat: 'V#A #B#' },
                { city: 'London', state: 'England', country: 'United Kingdom', postalFormat: 'SW# #AB' },
                { city: 'Sydney', state: 'NSW', country: 'Australia', postalFormat: '2###' },
                { city: 'Berlin', state: 'Berlin', country: 'Germany', postalFormat: '10###' }
            ],
            streets: ['Main St', 'Oak Ave', 'Pine St', 'Maple Ave', 'Cedar Blvd', 'Elm St', 'Park Ave', 'First St', 'Second St', 'Broadway', 'Washington St', 'Lincoln Ave', 'Madison St', 'Franklin Blvd', 'Jefferson Ave'],
            locationTypes: ['Home', 'Office', 'Work', 'Branch Office', 'Headquarters', 'Remote Office', 'Main Office', 'Satellite Office', 'Regional Office'],
            domains: ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'company.com', 'email.com', 'test.com', 'example.com', 'work.com']
        };
    }

    bindEvents() {
        // Open modal
        $(document).on('click', '#addUserBtn', () => {
            this.openModal();
        });

        // Close modal
        $(document).on('click', '.close, #cancelBtn', () => {
            this.closeModal();
        });

        // Close modal when clicking outside
        $(document).on('click', '#addUserModal', (e) => {
            if (e.target === document.getElementById('addUserModal')) {
                this.closeModal();
            }
        });

        // Handle form submission
        $(document).on('click', '#saveUserBtn', (e) => {
            e.preventDefault();
            this.submitForm();
        });

        // Handle form submission on Enter key
        this.form.on('submit', (e) => {
            e.preventDefault();
            this.submitForm();
        });

        // Real-time password confirmation validation
        $('#userPasswordConfirmation').on('input', () => {
            this.validatePasswordConfirmation();
        });

        // Generate random data button
        $(document).on('click', '#generateRandomBtn', () => {
            this.generateRandomWithFeedback();
        });
    }

    openModal() {
        this.resetForm();
        this.populateWithRandomData();
        this.modal.show();
        $('#userName').focus();
    }

    generateRandomData() {
        const firstName = this.getRandomItem(this.randomData.firstNames);
        const lastName = this.getRandomItem(this.randomData.lastNames);
        const locationCombo = this.getRandomItem(this.randomData.locationCombos);
        const street = this.getRandomItem(this.randomData.streets);
        const domain = this.getRandomItem(this.randomData.domains);
        const locationType = this.getRandomItem(this.randomData.locationTypes);

        return {
            name: `${firstName} ${lastName}`,
            email: `${firstName.toLowerCase()}.${lastName.toLowerCase()}${Math.floor(Math.random() * 999) + 1}@${domain}`,
            password: 'Password123!',
            location_name: locationType,
            address: `${Math.floor(Math.random() * 9999) + 1} ${street}`,
            city: locationCombo.city,
            state: locationCombo.state,
            country: locationCombo.country,
            postal_code: this.generatePostalCode(locationCombo.postalFormat),
            notes: `${locationType} address for ${firstName} ${lastName}. Generated for testing purposes.`
        };
    }

    getRandomItem(array) {
        return array[Math.floor(Math.random() * array.length)];
    }

    generatePostalCode(format = '#####') {
        // Generate postal code based on format
        return format.replace(/#/g, () => Math.floor(Math.random() * 10))
                    .replace(/[A-Z]/g, () => String.fromCharCode(65 + Math.floor(Math.random() * 26)));
    }

    populateWithRandomData() {
        const data = this.generateRandomData();

        // Populate user fields
        $('#userName').val(data.name);
        $('#userEmail').val(data.email);
        $('#userPassword').val(data.password);
        $('#userPasswordConfirmation').val(data.password);

        // Populate location fields
        $('#locationName').val(data.location_name);
        $('#locationAddress').val(data.address);
        $('#locationCity').val(data.city);
        $('#locationState').val(data.state);
        $('#locationCountry').val(data.country);
        $('#locationPostalCode').val(data.postal_code);
        $('#locationNotes').val(data.notes);
    }

    generateRandomWithFeedback() {
        const button = $('#generateRandomBtn');
        const originalText = button.text();

        // Show loading state
        button.text('🔄 Generating...').prop('disabled', true);

        // Add slight delay for visual feedback
        setTimeout(() => {
            this.populateWithRandomData();

            // Flash effect for generated fields
            $('.form-group input, .form-group textarea').each(function() {
                $(this).css('background-color', '#e7f3ff').animate({
                    backgroundColor: '#ffffff'
                }, 600);
            });

            // Reset button
            button.text(originalText).prop('disabled', false);

            // Show brief success message
            this.showBriefMessage('✅ Random data generated!', 'success');
        }, 300);
    }

    showBriefMessage(message, type = 'info') {
        const messageDiv = $(`
            <div style="
                position: absolute;
                top: 60px;
                right: 20px;
                background-color: ${type === 'success' ? '#d4edda' : '#e2e3e5'};
                color: ${type === 'success' ? '#155724' : '#495057'};
                padding: 8px 15px;
                border-radius: 4px;
                border: 1px solid ${type === 'success' ? '#c3e6cb' : '#ced4da'};
                font-size: 12px;
                z-index: 1001;
                opacity: 0;
            ">${message}</div>
        `);

        $('.modal-content').append(messageDiv);

        // Fade in, wait, then fade out
        messageDiv.animate({opacity: 1}, 200)
                  .delay(1500)
                  .animate({opacity: 0}, 200, function() {
                      $(this).remove();
                  });
    }

    closeModal() {
        this.modal.hide();
        this.clearErrors();
    }

    resetForm() {
        this.form[0].reset();
        this.clearErrors();
        $('#saveUserBtn').prop('disabled', false);
    }

    clearErrors() {
        $('.form-group').removeClass('error');
        $('.error-message').remove();
        $('.form-group input, .form-group textarea').css('border-color', '#ced4da');
    }

    showFieldError(fieldName, message) {
        const field = $(`[name="${fieldName}"]`);
        const formGroup = field.closest('.form-group');

        if (!formGroup.hasClass('error')) {
            formGroup.addClass('error');
            field.css('border-color', '#dc3545');
            field.after(`<div class="error-message" style="color: #dc3545; font-size: 12px; margin-top: 5px;">${message}</div>`);
        }
    }

    validatePasswordConfirmation() {
        const password = $('#userPassword').val();
        const confirmation = $('#userPasswordConfirmation').val();

        if (confirmation && password !== confirmation) {
            $('#userPasswordConfirmation').css('border-color', '#dc3545');
        } else {
            $('#userPasswordConfirmation').css('border-color', '#ced4da');
        }
    }

    validateForm() {
        this.clearErrors();
        let isValid = true;

        // Required fields validation
        const requiredFields = ['name', 'email', 'password', 'password_confirmation'];

        requiredFields.forEach(field => {
            const value = $(`[name="${field}"]`).val().trim();
            if (!value) {
                this.showFieldError(field, 'This field is required.');
                isValid = false;
            }
        });

        // Email validation
        const email = $('#userEmail').val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (email && !emailRegex.test(email)) {
            this.showFieldError('email', 'Please enter a valid email address.');
            isValid = false;
        }

        // Password validation
        const password = $('#userPassword').val();
        if (password && password.length < 8) {
            this.showFieldError('password', 'Password must be at least 8 characters long.');
            isValid = false;
        }

        // Password confirmation validation
        const confirmation = $('#userPasswordConfirmation').val();
        if (password !== confirmation) {
            this.showFieldError('password_confirmation', 'Password confirmation does not match.');
            isValid = false;
        }

        return isValid;
    }

    async submitForm() {
        if (!this.validateForm()) {
            return;
        }

        const submitButton = $('#saveUserBtn');
        submitButton.prop('disabled', true).text('Creating...');

        try {
            const formData = new FormData(this.form[0]);
            const data = Object.fromEntries(formData.entries());

            const response = await $.ajax({
                url: '/users',
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            if (response.success) {
                // Show success message using imported showNotification
                showNotification('success', response.message || 'User created successfully!');

                // Close modal
                this.closeModal();

                // Refresh the data grid
                if (typeof refreshUsersGrid === 'function') {
                    refreshUsersGrid();
                } else {
                    // Fallback: reload the page
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                }
            } else {
                // Show error message using imported showNotification
                showNotification('error', response.message || 'Error creating user');
            }

        } catch (xhr) {
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                // Handle validation errors
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(field => {
                    const messages = errors[field];
                    if (messages && messages.length > 0) {
                        this.showFieldError(field, messages[0]);
                    }
                });

                // Use showNotification for validation errors
                showNotification('error', 'Please correct the errors below.');
            } else {
                const message = xhr.responseJSON?.message || 'Error creating user. Please try again.';

                // Use showNotification for error notification
                showNotification('error', message);
            }
        } finally {
            submitButton.prop('disabled', false).text('Save User');
        }
    }
}

// Initialize modal when document is ready
$(document).ready(function() {
    new UserModal();
});
