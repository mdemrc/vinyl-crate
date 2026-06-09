$(function () {
    // 1. Live search on the browse page
    const $searchInput = $("#search-input");
    const $searchResults = $("#search-results");
    const $catalog = $("#catalog");
    let searchTimer = null;

    if ($searchInput.length > 0) {
        $searchInput.on("keyup input", function () {
            const fraza = $(this).val().trim();

            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            if (fraza.length < 2) {
                $searchResults.empty();
                $catalog.show();
                return;
            }

            searchTimer = setTimeout(function () {
                $.get("search_ajax.php", { fraza: fraza }, function (response) {
                    $searchResults.html(response);
                    $catalog.hide();
                }).fail(function () {
                    console.log("search request failed");
                });
            }, 300);
        });
    }

    // 2. Add or remove an album from the crate (collection / wishlist)
    $(document).on("click", ".crate-btn", function () {
        const $btn = $(this);
        const $controls = $btn.closest(".crate-controls");
        const albumId = $controls.data("album-id");
        const target = $btn.data("target");

        $.post("toggle_crate.php", { album_id: albumId, status: target }, function (response) {
            const state = (response || "").trim();

            if (state === "error") {
                alert("Operation failed. Please try again.");
                return;
            }

            $controls.find(".crate-btn").each(function () {
                const $b = $(this);
                const active = $b.data("target") === state;
                $b.attr("data-active", active ? "1" : "0");
                $b.text(active ? $b.attr("data-on") : $b.attr("data-off"));
            });

            if (state === "removed" && $controls.data("removable")) {
                $controls.closest(".album-card").fadeOut(350, function () {
                    $(this).remove();
                });
            }
        }).fail(function () {
            console.log("toggle_crate request failed");
        });
    });

    // 3. Delete one of my reviews without reloading
    $(document).on("click", ".delete-review", function () {
        const reviewId = $(this).data("review-id");

        if (!confirm("Delete this review?")) {
            return;
        }

        $.post("delete_review_ajax.php", { review_id: reviewId }, function (response) {
            if ((response || "").trim() === "ok") {
                $("#review-" + reviewId).fadeOut(350, function () {
                    $(this).remove();
                });
            } else {
                alert("Could not delete the review.");
            }
        }).fail(function () {
            console.log("delete_review request failed");
        });
    });

    // 4. Mark a review as helpful (cannot like your own)
    $(document).on("click", ".like-btn", function () {
        const $btn = $(this);
        const reviewId = $btn.data("review-id");

        $.post("toggle_review_like.php", { review_id: reviewId }, function (response) {
            const parts = (response || "").trim().split(":");

            if (parts[0] === "liked" || parts[0] === "unliked") {
                $btn.attr("data-liked", parts[0] === "liked" ? "1" : "0");
                $btn.find(".like-count").text("(" + parts[1] + ")");
            } else {
                alert("Could not register your vote.");
            }
        }).fail(function () {
            console.log("review like request failed");
        });
    });

    // 5. Tracklist editor on the album form
    $("#add-track").on("click", function () {
        const row =
            '<div class="track-row">' +
            '<input type="text" name="track_title[]" placeholder="Track title">' +
            '<input type="text" name="track_duration[]" class="duration-field" placeholder="m:ss">' +
            '<button type="button" class="btn small danger remove-track">&times;</button>' +
            "</div>";
        $("#track-rows").append(row);
    });

    $(document).on("click", ".remove-track", function () {
        const $rows = $("#track-rows .track-row");
        if ($rows.length > 1) {
            $(this).closest(".track-row").remove();
        } else {
            $(this).closest(".track-row").find("input").val("");
        }
    });
});
