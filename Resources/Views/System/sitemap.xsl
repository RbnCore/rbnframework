<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                exclude-result-prefixes="sitemap">
    <xsl:output method="html" encoding="UTF-8" indent="yes" />
    <xsl:template match="/">
        <xsl:variable name="firstUrl">
            <xsl:choose>
                <xsl:when test="sitemap:sitemapindex/sitemap:sitemap[1]/sitemap:loc">
                    <xsl:value-of select="sitemap:sitemapindex/sitemap:sitemap[1]/sitemap:loc"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="sitemap:urlset/sitemap:url[1]/sitemap:loc"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <xsl:variable name="domainRaw" select="substring-after($firstUrl, '://')"/>
        <xsl:variable name="domain">
            <xsl:choose>
                <xsl:when test="contains($domainRaw, '/')">
                    <xsl:value-of select="substring-before($domainRaw, '/')"/>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:value-of select="$domainRaw"/>
                </xsl:otherwise>
            </xsl:choose>
        </xsl:variable>
        <html lang="tr">
        <head>
            <title>XML Site Haritası - {{APP_NAME}}</title>
            <meta charset="utf-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet" />
            <style>
                body {
                    font-family: 'Inter', sans-serif;
                    background-color: #f1f5f9;
                    color: #334155;
                    margin: 0;
                    padding: 2rem 1rem;
                    line-height: 1.5;
                }
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
                    border: 1px solid #e2e8f0;
                    overflow: hidden;
                }
                .header {
                    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                    padding: 2.2rem 2rem;
                    position: relative;
                }
                .header h1 {
                    margin: 0;
                    font-size: 1.8rem;
                    font-weight: 700;
                    color: #ffffff;
                }
                .header p {
                    margin: 0.4rem 0 0 0;
                    color: #bfdbfe;
                    font-size: 0.95rem;
                }
                .nav-buttons {
                    display: flex;
                    gap: 0.5rem;
                    padding: 1.2rem 2rem;
                    background-color: #f8fafc;
                    border-bottom: 1px solid #e2e8f0;
                    flex-wrap: wrap;
                }
                .btn {
                    display: inline-flex;
                    align-items: center;
                    padding: 0.4rem 0.8rem;
                    background-color: #ffffff;
                    border: 1px solid #cbd5e1;
                    color: #475569;
                    text-decoration: none;
                    font-size: 0.85rem;
                    font-weight: 500;
                    border-radius: 6px;
                    transition: all 0.15s ease-in-out;
                }
                .btn:hover {
                    background-color: #3b82f6;
                    border-color: #3b82f6;
                    color: #ffffff;
                }
                .btn-home {
                    background-color: #475569;
                    color: #ffffff;
                    border-color: #475569;
                }
                .btn-home:hover {
                    background-color: #334155;
                }
                .btn-active {
                    background-color: #2563eb !important;
                    border-color: #2563eb !important;
                    color: #ffffff !important;
                }
                .content {
                    padding: 2rem;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    text-align: left;
                    font-size: 0.875rem;
                }
                th {
                    background-color: #f8fafc;
                    color: #64748b;
                    font-weight: 600;
                    text-transform: uppercase;
                    font-size: 0.75rem;
                    letter-spacing: 0.05em;
                    padding: 0.75rem 1rem;
                    border-bottom: 2px solid #e2e8f0;
                }
                td {
                    padding: 0.85rem 1rem;
                    border-bottom: 1px solid #e2e8f0;
                }
                td:first-child {
                    word-break: break-all;
                }
                td:not(:first-child) {
                    white-space: nowrap;
                }
                tr:hover td {
                    background-color: #f8fafc;
                }
                a.link {
                    color: #2563eb;
                    text-decoration: none;
                    font-weight: 500;
                }
                a.link:hover {
                    text-decoration: underline;
                }
                .badge {
                    display: inline-block;
                    padding: 0.2rem 0.4rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                    border-radius: 4px;
                    background-color: #e2e8f0;
                    color: #475569;
                }
                .badge-high {
                    background-color: #dbeafe;
                    color: #1e40af;
                }
                .footer {
                    padding: 1.2rem 2rem;
                    background-color: #f8fafc;
                    border-top: 1px solid #e2e8f0;
                    text-align: center;
                    font-size: 0.8rem;
                    color: #64748b;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>{{APP_NAME}} XML Site Haritası</h1>
                    <p>Bu site haritası arama motorlarının web sitesini taraması ve anlamlandırması için otomatik üretilmiştir.</p>
                </div>
                
                <div class="nav-buttons">
                    {{NAV_BUTTONS}}
                </div>

                <div class="content">
                    <xsl:choose>
                        <xsl:when test="sitemap:sitemapindex">
                            <p style="margin-top: 0; color: #64748b; font-size: 0.9rem;">Bu bir site haritası indeksidir. Aşağıdaki alt site haritalarını içerir:</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 70%">Site Haritası Bağlantısı</th>
                                        <th style="width: 30%">Son Güncelleme</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <xsl:for-each select="sitemap:sitemapindex/sitemap:sitemap">
                                        <tr>
                                            <td>
                                                <a href="{sitemap:loc}" class="link">
                                                    <xsl:value-of select="sitemap:loc" />
                                                </a>
                                            </td>
                                            <td>
                                                <xsl:value-of select="sitemap:lastmod" />
                                            </td>
                                        </tr>
                                    </xsl:for-each>
                                </tbody>
                            </table>
                        </xsl:when>
                        <xsl:otherwise>
                            <p style="margin-top: 0; color: #64748b; font-size: 0.9rem;">Toplam <strong style="color: #2563eb;"><xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></strong> adet bağlantı listeleniyor.</p>
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 55%">Bağlantı (URL)</th>
                                        <th style="width: 15%">Öncelik</th>
                                        <th style="width: 15%">Değişim Sıklığı</th>
                                        <th style="width: 15%">Son Güncelleme</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <xsl:for-each select="sitemap:urlset/sitemap:url">
                                        <tr>
                                            <td>
                                                <a href="{sitemap:loc}" class="link">
                                                    <xsl:value-of select="sitemap:loc" />
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge">
                                                    <xsl:if test="sitemap:priority &gt;= 0.8">
                                                        <xsl:attribute name="class">badge badge-high</xsl:attribute>
                                                    </xsl:if>
                                                    <xsl:value-of select="sitemap:priority" />
                                                </span>
                                            </td>
                                            <td>
                                                <xsl:value-of select="sitemap:changefreq" />
                                            </td>
                                            <td>
                                                <xsl:value-of select="sitemap:lastmod" />
                                            </td>
                                        </tr>
                                    </xsl:for-each>
                                </tbody>
                            </table>
                        </xsl:otherwise>
                    </xsl:choose>
                </div>
                <div class="footer">
                    <div>Tüm hakları saklıdır © {{APP_NAME}}</div>
                    <div style="margin-top: 0.25rem; font-size: 0.75rem; opacity: 0.8;"><xsl:value-of select="$domain" /></div>
                    <div style="margin-top: 0.5rem; font-size: 0.725rem; color: #94a3b8;">
                        Powered by <a href="{{FRAMEWORK_URL}}" target="_blank" style="color: #3b82f6; text-decoration: none; font-weight: 600;">{{FRAMEWORK_NAME}}</a>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    var path = window.location.pathname;
                    var links = document.querySelectorAll(".nav-buttons .btn");
                    links.forEach(function(link) {
                        var href = link.getAttribute("href");
                        if (href === path) {
                            link.classList.add("btn-active");
                        }
                    });
                });
            </script>
        </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
