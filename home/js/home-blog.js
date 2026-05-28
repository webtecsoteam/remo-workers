const BLOG_API = `${APP_URL}home/api/blogs.php`;
let blogFilterOptions = [{ id: 'all', label: 'All' }];
let blogPosts = [];
let blogPostsLoaded = false;
let blogPostsLoading = null;
let homeBlogFilter = 'all';
window.blogHubFilter = 'all';

function escapeBlogHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function blogMatchesFilter(post, filter) {
  if (!filter || filter === 'all') return true;
  return (post.category || '') === filter;
}

function getBlogFiltersList() {
  return blogFilterOptions.length ? blogFilterOptions : [{ id: 'all', label: 'All' }];
}

function renderHomeBlogFilters(activeFilter) {
  const wrap = document.getElementById('home-blog-filters');
  if (!wrap) return;
  wrap.innerHTML = getBlogFiltersList().map(f => {
    const on = f.id === activeFilter ? ' on' : '';
    return `<button type="button" class="tf${on}" data-blog-filter="${escapeBlogHtml(f.id)}">${escapeBlogHtml(f.label)}</button>`;
  }).join('');
}

function blogThumbHtml(post) {
  if (post.image_url) {
    return `<img src="${escapeBlogHtml(post.image_url)}" alt="" loading="lazy">`;
  }
  return escapeBlogHtml(post.emoji || '📚');
}

function registerBlogModal(post) {
  if (!post || !post.id) return;
  const metaParts = [
    post.category ? `<span>${escapeBlogHtml(post.category)}</span>` : '',
    `<span>${post.read_minutes || 1} min read</span>`,
    post.date_label ? `<span>${escapeBlogHtml(post.date_label)}</span>` : ''
  ].filter(Boolean).join('');
  const content = post.description
    ? post.description
    : '<p>No content available for this article yet.</p>';
  M[`blog-${post.id}`] = {
    t: post.name,
    b: `<div class="blog-full"><h3>${escapeBlogHtml(post.name)}</h3><div class="bmeta">${metaParts}</div>${content}</div>`
  };
}

async function loadBlogPosts() {
  if (blogPostsLoaded) return blogPosts;
  if (blogPostsLoading) return blogPostsLoading;
  blogPostsLoading = fetch(BLOG_API)
    .then(r => r.json())
    .then(json => {
      if (!json.success) throw new Error(json.message || 'Failed to load articles');
      blogPosts = json.data || [];
      if (json.filters && json.filters.length) {
        blogFilterOptions = json.filters;
      } else if (json.categories && json.categories.length) {
        blogFilterOptions = [{ id: 'all', label: 'All' }, ...json.categories.map(c => ({ id: c, label: c }))];
      }
      renderHomeBlogFilters(homeBlogFilter);
      blogPosts.forEach(registerBlogModal);
      blogPostsLoaded = true;
      return blogPosts;
    })
    .catch(err => {
      console.warn('Blog load error:', err);
      blogPosts = [];
      blogPostsLoaded = true;
      return blogPosts;
    })
    .finally(() => { blogPostsLoading = null; });
  return blogPostsLoading;
}

function renderHomeBlogGrid(posts, filter) {
  const grid = document.getElementById('home-blog-grid');
  if (!grid) return;
  const filtered = posts.filter(p => blogMatchesFilter(p, filter)).slice(0, 3);
  if (!filtered.length) {
    grid.innerHTML = '<div class="blog-grid-status">No published articles in this category yet. Check back soon or browse all topics.</div>';
    return;
  }
  grid.innerHTML = filtered.map(post => `
    <div class="blog-c" onclick="openBlogArticle(${post.id})" role="button" tabindex="0">
      <div class="blog-img${post.image_url ? ' has-img' : ''}">${blogThumbHtml(post)}</div>
      <div class="blog-body">
        <div class="blog-cat">${escapeBlogHtml(post.category || 'Article')}</div>
        <div class="blog-title">${escapeBlogHtml(post.name)}</div>
        <div class="blog-excerpt">${escapeBlogHtml(post.excerpt || '')}</div>
        <div class="blog-meta">${escapeBlogHtml(post.meta || '')}</div>
      </div>
    </div>
  `).join('');
  grid.querySelectorAll('.blog-c').forEach(el => io.observe(el));
}

function setHomeBlogFilter(filter, btn) {
  homeBlogFilter = filter;
  const wrap = document.getElementById('home-blog-filters');
  if (wrap) {
    wrap.querySelectorAll('.tf').forEach(b => b.classList.remove('on'));
    (btn || wrap.querySelector(`[data-blog-filter="${filter}"]`))?.classList.add('on');
  }
  loadBlogPosts().then(posts => renderHomeBlogGrid(posts, filter));
}

function blogFilterButtonsHtml(activeFilter, context) {
  const ctx = context === 'modal' ? 'modal' : 'home';
  return `<div class="blog-filter-bar" data-blog-filter-context="${ctx}">` +
    getBlogFiltersList().map(f => {
      const on = f.id === activeFilter ? ' blog-filter-on' : '';
      return `<button type="button" class="blog-filter-btn${on}" data-blog-filter="${escapeBlogHtml(f.id)}">${escapeBlogHtml(f.label)}</button>`;
    }).join('') +
    `</div>`;
}

function renderBlogHubList(posts, filter) {
  const filtered = posts.filter(p => blogMatchesFilter(p, filter));
  if (!filtered.length) {
    return '<div class="blog-grid-status">No articles in this category yet.</div>';
  }
  return `<div class="cat-modal-jobs">${filtered.map(post => `
    <div class="job-item" onclick="openBlogArticle(${post.id})">
      <div class="job-item-ico">${post.image_url ? `<img src="${escapeBlogHtml(post.image_url)}" alt="" style="width:40px;height:40px;border-radius:10px;object-fit:cover">` : escapeBlogHtml(post.emoji || '📚')}</div>
      <div class="job-item-body">
        <h4>${escapeBlogHtml(post.name)}</h4>
        <p>${escapeBlogHtml(post.excerpt || '')}</p>
        <div class="job-item-meta">
          ${post.category ? `<span class="jm">${escapeBlogHtml(post.category)}</span>` : ''}
          <span class="jm">${post.read_minutes} min read</span>
        </div>
      </div>
    </div>
  `).join('')}</div>`;
}

function refreshBlogAllModal(filter) {
  window.blogHubFilter = filter || 'all';
  const root = document.getElementById('blog-all-root');
  const html = blogFilterButtonsHtml(window.blogHubFilter, 'modal') +
    '<div id="blog-all-list">' + (blogPostsLoaded ? renderBlogHubList(blogPosts, window.blogHubFilter) : '<div class="blog-grid-status">Loading articles…</div>') + '</div>';
  if (root) {
    root.innerHTML = html;
  } else {
    M['blog-all'].b = `<div id="blog-all-root">${html}</div>`;
  }
}

function setBlogHubFilter(filter, btn) {
  window.blogHubFilter = filter;
  const bar = btn?.closest('.blog-filter-bar');
  if (bar) {
    bar.querySelectorAll('.blog-filter-btn').forEach(b => b.classList.remove('blog-filter-on'));
    btn.classList.add('blog-filter-on');
  }
  const list = document.getElementById('blog-all-list');
  if (list && blogPostsLoaded) {
    list.innerHTML = renderBlogHubList(blogPosts, filter);
    return;
  }
  loadBlogPosts().then(posts => {
    const el = document.getElementById('blog-all-list');
    if (el) el.innerHTML = renderBlogHubList(posts, filter);
    else refreshBlogAllModal(filter);
  });
}

function openBlogHub(filter) {
  const category = (filter ?? 'all').toString().trim();

  // Open the full Blog & Insights hub page (not a modal).
  if (!category || category.toLowerCase() === 'all') {
    window.location.href = `${APP_URL}blog`;
    return;
  }

  window.location.href = `${APP_URL}blog?category=${encodeURIComponent(category)}`;
}

function openBlogArticle(id) {
  const articleId = parseInt(id, 10);
  if (!articleId) return;

  // Open the full article page (not the home modal).
  // This ensures we always render the complete content, not the lightweight home embed.
  window.location.href = `${APP_URL}blog/${articleId}`;
}

document.getElementById('home-blog-filters')?.addEventListener('click', e => {
  const btn = e.target.closest('[data-blog-filter]');
  if (btn) setHomeBlogFilter(btn.getAttribute('data-blog-filter'), btn);
});

document.addEventListener('click', e => {
  const btn = e.target.closest('.blog-filter-bar[data-blog-filter-context="modal"] [data-blog-filter]');
  if (btn) setBlogHubFilter(btn.getAttribute('data-blog-filter'), btn);
});

function initHomeBlogFromServer() {
  const initial = window.HOME_BLOG_INITIAL;
  if (!initial || !Array.isArray(initial.posts)) return false;
  blogPosts = initial.posts;
  if (initial.filters && initial.filters.length) {
    blogFilterOptions = initial.filters;
  }
  blogPosts.forEach(registerBlogModal);
  blogPostsLoaded = true;
  renderHomeBlogFilters(homeBlogFilter);
  renderHomeBlogGrid(blogPosts, homeBlogFilter);
  refreshBlogAllModal('all');
  return true;
}

window.openBlogHub = openBlogHub;
window.openBlogArticle = openBlogArticle;

if (!initHomeBlogFromServer()) {
  loadBlogPosts().then(posts => {
    renderHomeBlogGrid(posts, homeBlogFilter);
    refreshBlogAllModal('all');
  });
}
