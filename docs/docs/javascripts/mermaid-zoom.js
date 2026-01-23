/**
 * Mermaid Diagram Pan/Zoom Functionality
 * 
 * Provides interactive pan and zoom capabilities for Mermaid diagrams using
 * the svg-pan-zoom library.
 * 
 * Features:
 * - Mouse wheel zoom
 * - Drag to pan
 * - Double-click to reset view
 * - Touch support for mobile devices
 * - Built-in zoom controls (+/- buttons)
 * - Works with MkDocs Material's instant navigation
 */

(function() {
  'use strict';

  // Configuration
  const CONFIG = {
    cdnUrl: 'https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js',
    initDelay: 500,
    panZoomOptions: {
      zoomEnabled: true,
      controlIconsEnabled: true,
      fit: true,
      center: true,
      minZoom: 0.5,
      maxZoom: 10,
      zoomScaleSensitivity: 0.3,
      dblClickZoomEnabled: false // We'll handle double-click ourselves
    }
  };

  // Track pan-zoom instances for cleanup
  const panZoomInstances = new Map();

  /**
   * Load the svg-pan-zoom library from CDN
   */
  function loadSvgPanZoom() {
    return new Promise((resolve, reject) => {
      // Check if already loaded
      if (typeof svgPanZoom !== 'undefined') {
        resolve();
        return;
      }

      const script = document.createElement('script');
      script.src = CONFIG.cdnUrl;
      script.onload = resolve;
      script.onerror = () => reject(new Error('Failed to load svg-pan-zoom library'));
      document.head.appendChild(script);
    });
  }

  /**
   * Apply pan-zoom functionality to a Mermaid SVG
   */
  function applyPanZoomToSvg(svg) {
    // Skip if already initialized
    if (svg.dataset.panZoomInitialized) {
      return;
    }

    const wrapper = svg.parentElement;
    if (!wrapper) return;

    // Add container class for styling
    wrapper.classList.add('mermaid-zoom-container');

    // Ensure SVG has proper dimensions for svg-pan-zoom
    if (!svg.getAttribute('width')) {
      svg.setAttribute('width', '100%');
    }
    if (!svg.getAttribute('height')) {
      svg.setAttribute('height', '100%');
    }

    try {
      const instance = svgPanZoom(svg, CONFIG.panZoomOptions);
      
      // Store instance for cleanup
      panZoomInstances.set(svg, instance);
      
      // Mark as initialized
      svg.dataset.panZoomInitialized = 'true';

      // Double-click to reset view
      svg.addEventListener('dblclick', (e) => {
        e.preventDefault();
        instance.resetZoom();
        instance.resetPan();
        instance.center();
      });

      // Handle window resize
      const resizeHandler = () => {
        instance.resize();
        instance.fit();
        instance.center();
      };
      
      window.addEventListener('resize', resizeHandler);
      
      // Store resize handler for cleanup
      svg._resizeHandler = resizeHandler;
      
    } catch (error) {
      console.warn('Failed to initialize pan-zoom for Mermaid diagram:', error);
    }
  }

  /**
   * Find and initialize all Mermaid diagrams on the page
   */
  function initializeMermaidDiagrams() {
    const diagrams = document.querySelectorAll('.mermaid svg');
    diagrams.forEach(applyPanZoomToSvg);
  }

  /**
   * Cleanup pan-zoom instances (for SPA navigation)
   */
  function cleanup() {
    panZoomInstances.forEach((instance, svg) => {
      try {
        if (svg._resizeHandler) {
          window.removeEventListener('resize', svg._resizeHandler);
          delete svg._resizeHandler;
        }
        instance.destroy();
        delete svg.dataset.panZoomInitialized;
      } catch (e) {
        // Ignore cleanup errors
      }
    });
    panZoomInstances.clear();
  }

  /**
   * Set up MutationObserver to detect dynamically rendered Mermaid diagrams
   */
  function setupObserver() {
    const observer = new MutationObserver((mutations) => {
      let shouldInit = false;
      
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === Node.ELEMENT_NODE) {
            // Check if it's an SVG or contains one
            if (node.tagName === 'svg' || 
                (node.querySelector && node.querySelector('.mermaid svg'))) {
              shouldInit = true;
            }
            // Check if it's a mermaid container
            if (node.classList && node.classList.contains('mermaid')) {
              shouldInit = true;
            }
          }
        });
      });
      
      if (shouldInit) {
        // Debounce to handle multiple rapid additions
        clearTimeout(window._mermaidZoomTimeout);
        window._mermaidZoomTimeout = setTimeout(initializeMermaidDiagrams, 100);
      }
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });

    return observer;
  }

  /**
   * Main initialization function
   */
  async function init() {
    try {
      await loadSvgPanZoom();
      
      // Set up observer for dynamic content
      setupObserver();
      
      // Initial application after delay (for Mermaid to render)
      setTimeout(initializeMermaidDiagrams, CONFIG.initDelay);
      
      // Handle MkDocs Material instant navigation
      // The document$ observable is provided by Material theme
      if (typeof document$ !== 'undefined') {
        document$.subscribe(() => {
          cleanup();
          setTimeout(initializeMermaidDiagrams, CONFIG.initDelay);
        });
      }
      
      // Also handle regular navigation events as fallback
      window.addEventListener('popstate', () => {
        setTimeout(initializeMermaidDiagrams, CONFIG.initDelay);
      });
      
    } catch (error) {
      console.error('Failed to initialize Mermaid zoom functionality:', error);
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
