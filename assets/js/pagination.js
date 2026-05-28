class Paginator {
    constructor(options) {
        this.container = typeof options.container === 'string' ? document.querySelector(options.container) : options.container;
        if (!this.container) return;
        
        this.itemsSelector = options.itemsSelector || 'tbody tr';
        this.itemsPerPage = options.itemsPerPage || 10;
        this.currentPage = 1;
        
        this.items = Array.from(this.container.querySelectorAll(this.itemsSelector));
        this.filteredItems = this.items; 
        
        this.setup();
    }
    
    setup() {
        this.renderControls();
        this.showPage(1);
    }
    
    filterItems(filterFn) {
        if (filterFn) {
            this.filteredItems = this.items.filter(filterFn);
            this.items.forEach(item => item.style.display = 'none');
        } else {
            this.filteredItems = this.items;
        }
        this.showPage(1);
        this.renderControls();
    }
    
    showPage(page) {
        this.currentPage = page;
        const start = (page - 1) * this.itemsPerPage;
        const end = start + this.itemsPerPage;
        
        this.filteredItems.forEach((item, index) => {
            if (index >= start && index < end) {
                if (item.tagName === 'TR') {
                    item.style.display = 'table-row';
                } else {
                    item.style.display = item.dataset.originalDisplay || 'block';
                    if (item.classList.contains('job-card') || item.classList.contains('prop-card')) {
                        item.style.display = 'flex';
                    }
                }
            } else {
                if (!item.dataset.originalDisplay && item.style.display !== 'none') {
                    item.dataset.originalDisplay = item.style.display || getComputedStyle(item).display;
                }
                item.style.display = 'none';
            }
        });
        
        this.updateControls();
    }
    
    renderControls() {
        const totalPages = Math.ceil(this.filteredItems.length / this.itemsPerPage);
        
        let controls = this.container.parentNode.querySelector('.paginator-controls');
        if (!controls) {
            controls = document.createElement('div');
            controls.className = 'paginator-controls';
            controls.style.display = 'flex';
            controls.style.justifyContent = 'center';
            controls.style.gap = '8px';
            controls.style.marginTop = '16px';
            controls.style.padding = '10px 0';
            
            // Insert after container
            if (this.container.nextSibling) {
                this.container.parentNode.insertBefore(controls, this.container.nextSibling);
            } else {
                this.container.parentNode.appendChild(controls);
            }
        }
        
        controls.innerHTML = '';
        if (totalPages <= 1) {
            controls.style.display = 'none';
            return;
        }
        controls.style.display = 'flex';
        
        const prevBtn = document.createElement('button');
        prevBtn.innerText = 'Prev';
        prevBtn.className = 'btn btn-outline btn-w btn-sm pag-prev';
        prevBtn.style.padding = '6px 12px';
        prevBtn.onclick = () => {
            if (this.currentPage > 1) this.showPage(this.currentPage - 1);
        };
        controls.appendChild(prevBtn);
        
        const info = document.createElement('span');
        info.className = 'pag-info';
        info.style.alignSelf = 'center';
        info.style.fontSize = '13px';
        info.style.color = 'var(--text-2, var(--uw-gray, #6b7280))';
        controls.appendChild(info);
        
        const nextBtn = document.createElement('button');
        nextBtn.innerText = 'Next';
        nextBtn.className = 'btn btn-outline btn-w btn-sm pag-next';
        nextBtn.style.padding = '6px 12px';
        nextBtn.onclick = () => {
            if (this.currentPage < totalPages) this.showPage(this.currentPage + 1);
        };
        controls.appendChild(nextBtn);
        
        this.updateControls();
    }
    
    updateControls() {
        const totalPages = Math.ceil(this.filteredItems.length / this.itemsPerPage);
        const controls = this.container.parentNode.querySelector('.paginator-controls');
        if (!controls) return;
        
        const info = controls.querySelector('.pag-info');
        if (info) {
            info.innerText = `Page ${this.currentPage} of ${totalPages || 1}`;
        }
        
        const prevBtn = controls.querySelector('.pag-prev');
        if (prevBtn) {
            prevBtn.disabled = this.currentPage === 1;
            prevBtn.style.opacity = this.currentPage === 1 ? '0.5' : '1';
            prevBtn.style.cursor = this.currentPage === 1 ? 'not-allowed' : 'pointer';
        }
        
        const nextBtn = controls.querySelector('.pag-next');
        if (nextBtn) {
            nextBtn.disabled = this.currentPage === totalPages || totalPages === 0;
            nextBtn.style.opacity = (this.currentPage === totalPages || totalPages === 0) ? '0.5' : '1';
            nextBtn.style.cursor = (this.currentPage === totalPages || totalPages === 0) ? 'not-allowed' : 'pointer';
        }
    }
}

function applyPagination(containerSelector, itemsSelector = 'tbody tr', itemsPerPage = 10) {
    return new Paginator({
        container: containerSelector,
        itemsSelector: itemsSelector,
        itemsPerPage: itemsPerPage
    });
}
