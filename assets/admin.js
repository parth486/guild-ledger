/**
 * Urbana Guild Ledger - Admin JavaScript
 * Version: 1.0.1
 * 
 * Changelog:
 * v1.0.1 (2025-10-08) - Beta: Added DataViews enhancements, live search, CSV export, quick actions
 * v1.0.0 (2025-09-29) - Initial release with form validation
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize DataViews enhancements
        initDataViewsEnhancements();
        
        // Initialize form enhancements (existing functionality)
        initFormEnhancements();
        
        // Auto-set today's date if date field is empty
        var dateField = $('#urbana_interaction_date');
        if (dateField.length && !dateField.val()) {
            var today = new Date().toISOString().split('T')[0];
            dateField.val(today);
        }
        
        // Form validation
        var form = $('#post');
        if (form.length) {
            form.on('submit', function(e) {
                var errors = [];

                // Check required fields
                var contactName = $('#urbana_contact_name').val().trim();
                var interactionDate = $('#urbana_interaction_date').val().trim();
                var interactionType = $('#urbana_interaction_type').val();

                if (!contactName) {
                    errors.push('Contact Name is required.');
                    $('#urbana_contact_name').focus();
                }

                if (!interactionDate) {
                    errors.push('Date is required.');
                    if (errors.length === 1) $('#urbana_interaction_date').focus();
                }

                if (!interactionType) {
                    errors.push('Interaction Type is required.');
                    if (errors.length === 1) $('#urbana_interaction_type').focus();
                }

                // Validate date format
                if (interactionDate && !isValidDate(interactionDate)) {
                    errors.push('Please enter a valid date.');
                    if (errors.length === 1) $('#urbana_interaction_date').focus();
                }

                // Show errors if any
                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Please fix the following errors:\n\n' + errors.join('\n'));
                    return false;
                }
            });
        }

        // Initialize dashboard charts on main page
        if ($('body').hasClass('toplevel_page_urbana-main')) {
            initDashboardCharts();
        }
        
        // Auto-generate title based on contact name and company
        $('#urbana_contact_name, #urbana_company_council, #urbana_interaction_date').on('blur', function() {
            updatePreviewTitle();
        });
        
        // Character counter for notes field
        var notesField = $('#urbana_notes');
        if (notesField.length) {
            var counter = $('<div class="character-counter" style="text-align: right; color: #666; font-size: 12px; margin-top: 5px;"></div>');
            notesField.closest('td').append(counter);
            
            function updateCharacterCount() {
                var content = notesField.val() || '';
                var wordCount = content.trim() ? content.trim().split(/\s+/).length : 0;
                var charCount = content.length;
                counter.text(charCount + ' characters, ' + wordCount + ' words');
            }
            
            notesField.on('input keyup', updateCharacterCount);
            updateCharacterCount();
        }
        
        // Enhance interaction type dropdown with icons
        enhanceInteractionTypeDropdown();
        
    });
    
    /**
     * Initialize DataViews enhancements for modern list display
     */
    function initDataViewsEnhancements() {
        // Check if we're on the ledger list page
        if (!$('body').hasClass('post-type-urbana_ledger') || !$('body').hasClass('edit-php')) {
            return;
        }
        
        // Add modern styling classes
        $('.wp-list-table').addClass('urbana-dataviews');

        // Remove any duplicate footer/header rows left in the table by WP or other plugins
        $('.post-type-urbana_ledger .wp-list-table tfoot').remove();
        cleanUpHeaderRows($('.post-type-urbana_ledger .wp-list-table tbody'));
        
        // Enhance search box
        enhanceSearchBox();
        
        // Add filters UI (date range, interaction type, lead status)
        addFilterControls();

        // Add column data attributes for mobile view
        addMobileDataAttributes();
        
        // Add real-time search (AJAX)
        addLiveSearch();
        
        // Enhance bulk actions
        enhanceBulkActions();
        
        // Add quick view functionality
        addQuickViewButtons();
        
        // Add export functionality
        addExportButton();
    }
    
    /**
     * Enhance the search box with modern features
     */
    function enhanceSearchBox() {
        var $searchBox = $('.search-box input[type="search"]');
        if ($searchBox.length) {
            // Use a professional text placeholder and add a class for scoped styling
            $searchBox.attr('placeholder', 'Search by contact, company, or notes...').addClass('urbana-search-input').attr('aria-label', 'Search ledger entries');

            // Remove any previously injected inline icon element — we now use a background SVG inside the input
            $searchBox.prev('.urbana-search-icon').remove();

            // Add clear button if not present
            if (!$searchBox.next('.search-clear').length) {
                $searchBox.after('<button type="button" class="button search-clear" style="margin-left: 8px;">Clear</button>');
                
                $('.search-clear').on('click', function() {
                    $searchBox.val('').focus();
                    $(this).closest('form').submit();
                });
            }
        }
    }
    
    /**
     * Add data attributes for mobile responsive tables
     */
    function addMobileDataAttributes() {
        // Use the full set of header cells (td/th) to match row cell indices exactly
        $('.wp-list-table thead tr').first().children().each(function(index) {
            var columnName = $(this).text().trim();
            $('.wp-list-table tbody tr').each(function() {
                var $cell = $(this).find('td, th').eq(index);
                if ($cell.length && columnName) {
                    $cell.attr('data-colname', columnName);
                }
            });
        });
    }
    
    /**
     * Add live search filtering (robust selectors + submit handling)
     */
    function addLiveSearch() {
        // Debounced input => AJAX (trigger when at least 3 letters are entered)
        var searchTimeout;

        // Delegate to capture the visible search input reliably
        $(document).on('input', '.wrap input[name="s"], .wrap input[type="search"]', function() {
            clearTimeout(searchTimeout);
            var $this = $(this);
            var searchTerm = $this.val().trim();
            var letterCount = (searchTerm.match(/[a-zA-Z]/g) || []).length;

            searchTimeout = setTimeout(function() {
                if (searchTerm.length === 0 || letterCount >= 3) {
                    fetchEntries({ s: searchTerm, page: 1 });
                }
            }, 150);
        });

        // Intercept search form submit and search button clicks within the wrap
        $(document).on('submit', '.wrap form', function(e) {
            var $btn = $(document.activeElement);
            // Only intercept if the form contains a search input for this screen
            if ($(this).find('input[name="s"], input[type="search"]').length) {
                e.preventDefault();
                var s = $(this).find('input[name="s"], input[type="search"]').first().val().trim();
                fetchEntries({ s: s, page: 1 });
            }
        });

        $(document).on('click', '.wrap .search-submit', function(e) {
            e.preventDefault();
            var s = $('.wrap input[name="s"], .wrap input[type="search"]').first().val().trim();
            fetchEntries({ s: s, page: 1 });
        });

        // Trigger initial load
        fetchEntries({ page: 1 });
    }

    /**
     * Add filter controls (date range, interaction type, lead status)
     */
    function addFilterControls() {
        // Find several potential insertion points for the filters
        var $tablenav = $('.tablenav.top').first();
        if (!$tablenav.length) return;

        var $afterBulk = $tablenav.find('.bulkactions').first();
        var $alignLeft = $tablenav.find('.alignleft.actions, .alignleft').first();
        var $searchBox = $tablenav.find('.search-box').first();

        var $filters = $('<div class="urbana-filters" style="display:flex; gap:8px; align-items:center; margin-left:12px;"></div>');
        var typeOptions = '<option value="">All Types</option>' +
            '<option value="email">Email</option>' +
            '<option value="video_call">Video Call</option>' +
            '<option value="in_person">In-Person</option>' +
            '<option value="phone_call">Phone Call</option>';

        $filters.append('<input type="date" id="filter-start" aria-label="Start date" placeholder="Start" />');
        $filters.append('<input type="date" id="filter-end" aria-label="End date" placeholder="End" />');
        $filters.append('<select id="filter-interaction">' + typeOptions + '</select>');
        $filters.append('<select id="filter-lead-status"><option value="">All Statuses</option></select>');
        $filters.append('<button type="button" id="urbana-filter-apply" class="button">Filter</button>');
        $filters.append('<button type="button" id="urbana-filter-reset" class="button button-secondary">Reset</button>');

        // Prefer inserting filters into the bulkactions container so they appear to the left
        if ($afterBulk.length) {
            $afterBulk.append($filters);
        } else if ($alignLeft.length) {
            $alignLeft.append($filters);
        } else if ($searchBox.length) {
            $searchBox.append($filters);
        } else {
            // Fallback: append into tablenav
            $tablenav.append($filters);
        }

        // If there is a months dropdown (All dates) try to hide it
        var $dateSelect = $tablenav.find('select[name="m"]').first();
        if ($dateSelect.length) {
            $dateSelect.hide();
            // Hide the default Filter button to avoid confusion if present
            $tablenav.find('#post-query-submit, input#post-query-submit').hide();
        }

        // Populate lead statuses via REST
        fetch(urbanaLedgerData.restUrl + 'urbana-guild-ledger/v1/lead-statuses', {
            headers: { 'X-WP-Nonce': urbanaLedgerData.nonce }
        }).then(function(res) {
            if (!res.ok) {
                return res.text().then(function(body) { throw { status: res.status, body: body }; });
            }
            // try parse JSON, but fall back to text for debugging
            return res.json().catch(function() { return res.text().then(function(body){ throw { status: res.status, body: body }; }); });
        }).then(function(data) {
            var $select = $('#filter-lead-status');
            data.forEach(function(t) {
                $select.append('<option value="' + t.slug + '">' + t.name + '</option>');
            });
        }).catch(function(err){
            console.warn('Could not load lead statuses:', err);
            // do not disturb the user for failure to load statuses — leave the select as 'All Statuses'
        });

        // Wire up change events - instant apply for dropdowns
        $filters.on('change', 'select', function() {
            fetchEntries({ page: 1 });
        });

        // Apply button for manual filtering
        $(document).on('click', '#urbana-filter-apply', function(e) {
            e.preventDefault();
            fetchEntries({ page: 1 });
        });

        // Reset button clears filters and search then reloads
        $(document).on('click', '#urbana-filter-reset', function(e) {
            e.preventDefault();
            $('#filter-start').val('');
            $('#filter-end').val('');
            $('#filter-interaction').val('');
            $('#filter-lead-status').val('');
            // Clear visible search input(s)
            $('.wrap input[name="s"], .wrap input[type="search"]').each(function() { $(this).val(''); });
            fetchEntries({ page: 1 });
            showNotice('Filters reset', 'success');
        });

        // Enable auto-apply when both dates are set
        $filters.on('change', 'input[type="date"]', function() {
            var start = $('#filter-start').val();
            var end = $('#filter-end').val();
            if (start && end) {
                fetchEntries({ page: 1 });
            }
        });

        // Ensure filters are visible (in case some admin CSS hides unknown children)
        $filters.css('display', 'flex');
        // Provide a small dev hint via data attribute so we can detect presence
        $filters.attr('data-urbana-inserted', '1');
    }

    /**
     * Fetch filtered entries via REST and render rows
     */
    function fetchEntries(params) {
        params = params || {};
        // If caller doesn't supply s, prefer any visible search input value
        var s = params.s !== undefined ? params.s : ($('input[name="s"]:visible, input[type="search"]:visible').first().val() || '').trim();
        var start = $('#filter-start').val() || '';
        var end = $('#filter-end').val() || '';
        var type = $('#filter-interaction').val() || '';
        var status = $('#filter-lead-status').val() || '';
        var page = params.page || 1;
        var per_page = params.per_page || 20;

        var qs = '?per_page=' + encodeURIComponent(per_page) + '&page=' + encodeURIComponent(page);
        if (s) qs += '&s=' + encodeURIComponent(s);
        if (start) qs += '&start_date=' + encodeURIComponent(start);
        if (end) qs += '&end_date=' + encodeURIComponent(end);
        if (type) qs += '&interaction_type=' + encodeURIComponent(type);
        if (status) qs += '&lead_status=' + encodeURIComponent(status);

        var $table = $('.wp-list-table');
        showLoading();
        $table.addClass('loading');
        fetch(urbanaLedgerData.restUrl + 'urbana-guild-ledger/v1/entries' + qs, {
            headers: { 'X-WP-Nonce': urbanaLedgerData.nonce }
        }).then(function(res) {
            if (!res.ok) {
                return res.text().then(function(body) { throw { status: res.status, body: body }; });
            }
            return res.json().catch(function() { return res.text().then(function(body){ throw { status: res.status, body: body }; }); });
        }).then(function(data) {
            renderRows(data.items);
        }).catch(function(err) {
            console.error('Error fetching entries:', err);
            var $tbody = $('.wp-list-table tbody');
            $tbody.empty();
            // Remove any accidental header-like rows in the tbody that might be left by WP or other scripts
            if (typeof cleanUpHeaderRows === 'function') {
                cleanUpHeaderRows($tbody);
            }
            var message = 'Unable to load entries.';
            if (err && err.body) {
                message += ' Server response: ' + String(err.body).slice(0, 300);
            }
            $tbody.append('<tr class="no-results-message"><td colspan="100" style="text-align:center; padding: 40px; color: #666;">' + message + '</td></tr>');
            showNotice('Unable to load entries (see console).', 'error');
        }).finally(function() {
            $table.removeClass('loading');
            hideLoading();
        });
    }

    /**
     * Remove accidental header rows that may be injected into the tbody
     * This function is defensive: it looks for TH cells, links that sort columns,
     * or text that matches known header labels and removes those rows.
     */
    function cleanUpHeaderRows($tbody) {
        $tbody.find('tr').each(function() {
            var $r = $(this);
            var text = $r.text().toUpperCase();

            // If it contains a table header cell, remove it
            if ($r.find('th').length > 0) {
                $r.remove();
                return;
            }

            // If it contains a sorting link (common in header rows), remove it
            if ($r.find('a[href*="orderby="]').length > 0) {
                $r.remove();
                return;
            }

            // Match common header label text (defensive against small variations)
            var headerKeywords = ['CONTACT NAME', 'COMPANY/COUNCIL', 'INTERACTION TYPE', 'TITLE', 'LEAD STATUS', 'DATE'];
            for (var i = 0; i < headerKeywords.length; i++) {
                if (text.indexOf(headerKeywords[i]) !== -1) {
                    $r.remove();
                    return;
                }
            }

            // Fallback: if the row contains many uppercase short words typical of header rows,
            // remove it (helps when markup is altered by other plugins)
            var words = text.split(/\s+/).filter(Boolean);
            var uppercaseCount = 0;
            for (var j = 0; j < words.length; j++) {
                if (words[j] === words[j].toUpperCase() && words[j].length > 1) uppercaseCount++;
            }
            if (uppercaseCount >= 3) {
                $r.remove();
            }
        });
    }

    /**
     * Render table rows from REST data
     */
    function renderRows(items) {
        var $tbody = $('.wp-list-table tbody');
        $tbody.empty();

        if (!items || items.length === 0) {
            $tbody.append('<tr class="no-results-message"><td colspan="100" style="text-align:center; padding: 40px; color: #666;">No entries found</td></tr>');
            // Ensure we also remove any accidental header rows even when there are no items
            cleanUpHeaderRows($tbody);
            return;
        }

        items.forEach(function(item) {
            var $tr = $('<tr></tr>');

            // Checkbox column (keeps alignment with WP list table)
            $tr.append('<td class="check-column"><input type="checkbox" name="post[]" value="' + item.id + '" /></td>');

            // Title column (primary)
            var actionsHtml = '<div class="row-actions">' +
                '<span class="edit"><a href="' + item.edit_url + '">Edit</a></span>' +
                '<span class="inline hide-if-no-js"><a href="#" class="quick-edit" data-id="' + item.id + '">Quick&nbsp;Edit</a></span>' +
                '<span class="trash"><a class="submitdelete" href="' + urbanaLedgerData.adminUrl + 'post.php?post=' + item.id + '&action=trash">Trash</a></span>' +
                '</div>';

            // Wrap title and actions in a .title-inner container so we can vertically center title and show actions beneath on hover
            var $titleTd = $('<td class="title column-title column-primary">' +
                '<div class="title-inner">' +
                    '<div class="title-text"><strong><a class="row-title" href="' + item.edit_url + '">' + escapeHtml(item.title) + '</a></strong></div>' +
                    actionsHtml +
                '</div>' +
                '</td>');
            $tr.append($titleTd);

            $tr.append('<td class="column-contact_name">' + (item.contact ? escapeHtml(item.contact) : '') + '</td>');
            $tr.append('<td class="column-company_council">' + (item.company ? escapeHtml(item.company) : '') + '</td>');
            $tr.append('<td class="column-interaction_date">' + (item.date ? escapeHtml(item.date) : '') + '</td>');
            var typeLabel = item.interaction_type ? item.interaction_type.replace('_', ' ') : '';
            $tr.append('<td class="column-interaction_type"><span class="interaction-type interaction-type--' + (item.interaction_type || '') + '">' + escapeHtml(typeLabel) + '</span></td>');
            $tr.append('<td class="column-lead_status">' + (item.lead_status ? escapeHtml(item.lead_status) : '') + '</td>');

            $tbody.append($tr);
        });

        // Recalculate mobile data-colname attributes
        addMobileDataAttributes();

        // Clean up accidental header rows inside tbody (some WP screens/plugins may duplicate headers)
        $tbody.find('tr').each(function() {
            var $r = $(this);
            // If row contains header cells or suspicious header text, remove it
            var text = $r.text().toUpperCase();
            if ($r.find('th').length > 0 || text.indexOf('CONTACT NAME') !== -1 || text.indexOf('COMPANY/COUNCIL') !== -1 || text.indexOf('INTERACTION TYPE') !== -1) {
                $r.remove();
            }
        });
    }

    /**
     * Basic HTML escape
     */
    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    
    /**
     * Enhance bulk actions with modern styling
     */
    function enhanceBulkActions() {
        $('.bulkactions select, .bulkactions .button').css({
            'border-radius': '6px',
            'padding': '8px 12px'
        });
    }
    
    /**
     * Add quick view buttons to each row
     */
    function addQuickViewButtons() {
        $('.wp-list-table tbody tr').each(function() {
            var $row = $(this);
            var editLink = $row.find('.row-title').attr('href');
            
            if (editLink && !$row.find('.quick-view-btn').length) {
                var $actionsCell = $row.find('.column-title .row-actions');
                if ($actionsCell.length) {
                    $actionsCell.append(' | <span class="quick-view"><a href="#" class="quick-view-btn" data-url="' + editLink + '">Quick View</a></span>');
                }
            }
        });
        
        // Handle quick view clicks
        $(document).on('click', '.quick-view-btn', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            // For now, just navigate to the edit page
            window.location.href = url;
        });

        // Handle Quick Edit action (attempt inline edit if available, otherwise fallback to Edit screen)
        $(document).on('click', '.quick-edit', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            // If WP inline edit is available, try to call it
            try {
                if (typeof inlineEditPost !== 'undefined' && typeof inlineEditPost.edit === 'function') {
                    // inlineEditPost.edit expects an ID or element - safe fallback
                    inlineEditPost.edit(id);
                    return;
                }
            } catch (ex) {
                // ignore and fallback
            }

            // Fallback: go to edit page
            window.location.href = urbanaLedgerData.adminUrl + 'post.php?post=' + id + '&action=edit';
        });
    }
    
    /**
     * Add export functionality
     */
    function addExportButton() {
        if (!$('#export-ledger-btn').length) {
            // Prefer placing after the Apply button if present
            var $apply = $('.tablenav.top .bulkactions .button').first();
            if ($apply.length) {
                $apply.after('<button type="button" id="export-ledger-btn" class="button" style="margin-left: 8px;">Export CSV</button>');
            } else if ($('.tablenav.top .alignleft.actions').length) {
                $('.tablenav.top .alignleft.actions').first().append('<button type="button" id="export-ledger-btn" class="button">Export CSV</button>');
            } else {
                $('.tablenav.top').first().append('<button type="button" id="export-ledger-btn" class="button">Export CSV</button>');
            }

            $('#export-ledger-btn').on('click', function() {
                exportToCSV();
            });
        }
    }

    /**
     * Show loading overlay over the list table
     */
    function showLoading() {
        var $container = $('.post-type-urbana_ledger .wp-list-table').first();
        if (!$container.length) return;
        // Avoid multiple overlays
        if ($container.find('.urbana-loading-overlay').length) return;
        var $overlay = $('<div class="urbana-loading-overlay"><div class="urbana-loading-spinner"></div></div>');
        $overlay.appendTo($container.parent());
    }

    function hideLoading() {
        $('.post-type-urbana_ledger .urbana-loading-overlay').remove();
    }
    
    /**
     * Export table data to CSV
     */
    function exportToCSV() {
        var csv = [];
        var rows = $('.wp-list-table tr:visible');
        
        // Get headers
        var headers = [];
        rows.first().find('th').each(function() {
            var text = $(this).text().trim();
            if (text && text !== '' && !$(this).hasClass('check-column')) {
                headers.push(text);
            }
        });
        csv.push(headers.join(','));
        
        // Get data rows
        rows.slice(1).each(function() {
            var row = [];
            $(this).find('td').each(function(index) {
                if (!$(this).hasClass('check-column') && index < headers.length) {
                    var text = $(this).text().trim().replace(/\s+/g, ' ').replace(/,/g, ';');
                    row.push('"' + text + '"');
                }
            });
            if (row.length > 0) {
                csv.push(row.join(','));
            }
        });
        
        // Download CSV
        var csvContent = csv.join('\n');
        var blob = new Blob([csvContent], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'urbana-ledger-export-' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        // Show success message
        showNotice('Ledger entries exported successfully!', 'success');
    }
    
    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible" style="margin: 15px 0;"><p>' + message + '</p></div>');
        $('.wp-header-end').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Initialize form enhancements (existing functionality)
     */
    function initFormEnhancements() {
        // Placeholder for future form-related enhancements
    }

    /**
     * Check if a date string is valid
     */
    function isValidDate(dateString) {
        var date = new Date(dateString);
        return date instanceof Date && !isNaN(date) && dateString === date.toISOString().split('T')[0];
    }
    
    /**
     * Update preview title based on form fields
     */
    function updatePreviewTitle() {
        var contactName = $('#urbana_contact_name').val().trim();
        var company = $('#urbana_company_council').val().trim();
        var date = $('#urbana_interaction_date').val().trim();
        
        if (!contactName) return;
        
        var title = contactName;
        if (company) {
            title += ' (' + company + ')';
        }
        if (date) {
            var dateObj = new Date(date);
            if (!isNaN(dateObj)) {
                title += ' - ' + dateObj.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric', 
                    year: 'numeric' 
                });
            }
        }
        
        // Update the title field (hidden from user but used by WordPress)
        $('#title').val(title);
        
        // Show preview
        var preview = $('#title-preview');
        if (!preview.length) {
            preview = $('<div id="title-preview" style="font-weight: bold; color: #555; margin-top: 10px;"></div>');
            $('#urbana_contact_name').closest('td').append('<p class="description"><strong>Generated Title:</strong></p>').append(preview);
        }
        preview.text(title);
    }
    
    /**
     * Enhance interaction type dropdown with visual indicators
     */
    function enhanceInteractionTypeDropdown() {
        var select = $('#urbana_interaction_type');
        if (!select.length) return;
        
        var icons = {
            'email': '📧',
            'video_call': '📹',
            'in_person': '👤',
            'phone_call': '📞'
        };
        
        select.find('option').each(function() {
            var $option = $(this);
            var value = $option.val();
            if (icons[value]) {
                $option.text(icons[value] + ' ' + $option.text());
            }
        });
    }
    
    /**
     * Add quick search functionality to the list table
     */
    function addQuickSearch() {
        var searchBox = $('.search-box input[name="s"]');
        if (searchBox.length) {
            searchBox.attr('placeholder', 'Search contacts, companies, or notes...');
            
            // Add search tips
            var tips = $('<p class="description" style="margin-top: 5px;">Tip: Search by contact name, company, or notes content.</p>');
            searchBox.closest('.search-box').append(tips);
        }
    }
    
    /**
     * Initialize dashboard charts
     */
    function initDashboardCharts(retries) {
        retries = typeof retries === 'undefined' ? 0 : retries;
        if (typeof Chart === 'undefined') {
            if (retries < 8) {
                setTimeout(function() { initDashboardCharts(retries + 1); }, 250);
            }
            return;
        }
        fetch(urbanaLedgerData.restUrl + 'urbana-guild-ledger/v1/stats', { headers: { 'X-WP-Nonce': urbanaLedgerData.nonce } })
            .then(function(res) {
                if (!res.ok) {
                    return res.json().then(function(body) { throw { status: res.status, body: body }; });
                }
                return res.json();
            })
            .then(function(data) {
                // Interactions by month (line)
                var months = data.by_month.map(function(m) { return m.month; });
                var counts = data.by_month.map(function(m) { return m.count; });
                var ctx = document.getElementById('chart-interactions-months');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: { labels: months, datasets: [{ label: 'Interactions', data: counts, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.08)', fill: true }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                // Interactions by type (bar)
                var typeLabels = Object.keys(data.by_type);
                var typeCounts = typeLabels.map(function(k) { return data.by_type[k]; });
                var ctx2 = document.getElementById('chart-interactions-type');
                if (ctx2) {
                    new Chart(ctx2, {
                        type: 'bar',
                        data: { labels: typeLabels.map(function(t){ return t.replace('_',' '); }), datasets: [{ label: 'Interactions', data: typeCounts, backgroundColor: ['#60a5fa','#a78bfa','#34d399','#fbbf24'] }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }

                // Lead status (doughnut)
                var statusLabels = Object.keys(data.by_status);
                var statusCounts = statusLabels.map(function(k) { return data.by_status[k]; });
                var ctx3 = document.getElementById('chart-lead-status');
                if (ctx3) {
                    new Chart(ctx3, {
                        type: 'doughnut',
                        data: { labels: statusLabels, datasets: [{ data: statusCounts, backgroundColor: ['#60a5fa','#f97316','#34d399','#f472b6','#f43f5e'] }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }
            });
    }

    /**
     * Get URL parameter by name
     */
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }
    
})(jQuery);