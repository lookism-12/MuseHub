document.addEventListener('DOMContentLoaded', function () {
    const likeButtons = document.querySelectorAll('.like-btn');

    likeButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const artworkId = this.dataset.artworkId;
            const url = this.dataset.url;
            const icon = this.querySelector('i');
            const countBadge = this.closest('.card').querySelector('.likes-count');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    if (response.status === 401) {
                        alert('Vous devez être connecté pour aimer une œuvre.');
                        return null;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data) {
                        // Update UI
                        if (data.liked) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            this.classList.add('active'); // Optional styling
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            this.classList.remove('active');
                        }

                        if (countBadge) {
                            countBadge.textContent = data.likes_count;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    });
});
