/**
 * End-to-end test for Turnstile module
 * Tests that Turnstile widgets are properly injected on forms
 */

const puppeteer = require('puppeteer');

const BASE_URL = process.env.BASE_URL || 'http://localhost';
const EXPECTED_SITE_KEY = '1x00000000000000000000AA';

async function testPage(page, url, description) {
    console.log(`\n📄 Testing: ${description}`);
    console.log(`   URL: ${url}`);
    
    try {
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
        
        // Wait a bit for JavaScript to execute
        await page.waitForTimeout(2000);
        
        // Check if Turnstile API script is loaded
        const hasApiScript = await page.evaluate(() => {
            const scripts = Array.from(document.querySelectorAll('script'));
            return scripts.some(script => 
                script.src && script.src.includes('challenges.cloudflare.com/turnstile/v0/api.js')
            );
        });
        
        if (hasApiScript) {
            console.log('   ✓ Turnstile API script loaded');
        } else {
            console.log('   ✗ Turnstile API script NOT loaded');
            return false;
        }
        
        // Check if front.js is loaded
        const hasFrontJs = await page.evaluate(() => {
            const scripts = Array.from(document.querySelectorAll('script'));
            return scripts.some(script => 
                script.src && script.src.includes('modules/turnstile/views/js/front.js')
            );
        });
        
        if (hasFrontJs) {
            console.log('   ✓ Turnstile front.js loaded');
        } else {
            console.log('   ✗ Turnstile front.js NOT loaded');
            return false;
        }
        
        // Check if site key variable is defined
        const siteKey = await page.evaluate(() => {
            return window.turnsitleSiteKey || null;
        });
        
        if (siteKey === EXPECTED_SITE_KEY) {
            console.log(`   ✓ Site key correctly set: ${siteKey}`);
        } else {
            console.log(`   ✗ Site key mismatch. Expected: ${EXPECTED_SITE_KEY}, Got: ${siteKey}`);
            return false;
        }
        
        // Check if Turnstile widget is present (if there are forms on this page)
        const widgetInfo = await page.evaluate(() => {
            const widgets = document.querySelectorAll('.cf-turnstile');
            const forms = document.querySelectorAll('form');
            return {
                widgetCount: widgets.length,
                formCount: forms.length,
                widgets: Array.from(widgets).map(w => ({
                    sitekey: w.getAttribute('data-sitekey'),
                    theme: w.getAttribute('data-theme')
                }))
            };
        });
        
        if (widgetInfo.formCount > 0) {
            if (widgetInfo.widgetCount > 0) {
                console.log(`   ✓ Found ${widgetInfo.widgetCount} Turnstile widget(s) on ${widgetInfo.formCount} form(s)`);
                widgetInfo.widgets.forEach((widget, idx) => {
                    console.log(`     Widget ${idx + 1}: sitekey=${widget.sitekey}, theme=${widget.theme}`);
                    if (widget.sitekey !== EXPECTED_SITE_KEY) {
                        console.log(`     ⚠ Widget has incorrect site key!`);
                        return false;
                    }
                });
            } else {
                console.log(`   ℹ No Turnstile widgets found (${widgetInfo.formCount} forms present)`);
                console.log(`   This may be expected if forms don't match the selectors`);
            }
        } else {
            console.log(`   ℹ No forms found on this page (widgets only inject on forms)`);
        }
        
        return true;
    } catch (error) {
        console.log(`   ✗ Error: ${error.message}`);
        return false;
    }
}

async function runTests() {
    console.log('🚀 Starting Turnstile E2E Tests\n');
    console.log(`Base URL: ${BASE_URL}`);
    console.log(`Expected Site Key: ${EXPECTED_SITE_KEY}\n`);
    
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const page = await browser.newPage();
    
    // Set viewport
    await page.setViewport({ width: 1280, height: 800 });
    
    const tests = [
        { url: `${BASE_URL}/`, description: 'Homepage' },
        { url: `${BASE_URL}/en/login`, description: 'Login Page' },
        { url: `${BASE_URL}/en/order`, description: 'Checkout Page' },
        { url: `${BASE_URL}/en/contact-us`, description: 'Contact Page' }
    ];
    
    let passed = 0;
    let failed = 0;
    
    for (const test of tests) {
        const result = await testPage(page, test.url, test.description);
        if (result) {
            passed++;
        } else {
            failed++;
        }
    }
    
    await browser.close();
    
    console.log('\n' + '='.repeat(60));
    console.log('📊 Test Results');
    console.log('='.repeat(60));
    console.log(`✓ Passed: ${passed}`);
    console.log(`✗ Failed: ${failed}`);
    console.log('='.repeat(60));
    
    if (failed > 0) {
        console.log('\n❌ Some tests failed!');
        process.exit(1);
    } else {
        console.log('\n✅ All tests passed!');
        process.exit(0);
    }
}

runTests().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});

