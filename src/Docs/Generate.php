<?php

namespace Grafite\Support\Docs;

class Generate
{
    public static function handle()
    {
        return <<<"EOT"
            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    let _docsLinkContainer = document.createElement('div');
                        _docsLinkContainer.style.position = "fixed";
                        _docsLinkContainer.style.bottom = "0";
                        _docsLinkContainer.style.borderTop = "1px solid #333";
                        _docsLinkContainer.style.borderLeft = "1px solid #333";
                        _docsLinkContainer.style.right = "0";
                        _docsLinkContainer.style.width = "40px";
                        _docsLinkContainer.style.height = "40px";
                        _docsLinkContainer.style.background = "var(--bs-body-bg)";
                        _docsLinkContainer.style.zIndex = "40000";

                    let _docsLink = document.createElement('a');
                        _docsLink.href = "/docs";
                        _docsLink.classList.add("text-success");
                        _docsLink.style.display = "block";
                        _docsLink.style.marginTop = "6px";
                        _docsLink.style.marginLeft = "12px";
                    let _docsLinkIcon = document.createElement('span');
                        _docsLinkIcon.classList.add("fas");
                        _docsLinkIcon.classList.add("fa-book");

                    _docsLink.appendChild(_docsLinkIcon);
                    _docsLinkContainer.appendChild(_docsLink);
                    document.body.appendChild(_docsLinkContainer);
                });
            </script>
        EOT;
    }
}
