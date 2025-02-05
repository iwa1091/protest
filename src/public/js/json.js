const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const commentForm = document.getElementById('comment__form');
const commentList = document.getElementById('comments__list');

commentForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const commentText = formData.get('comment');
    
    fetch(this.action, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ comment: commentText })
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                document.querySelector('.comment__count').textContent = data.count;
                document.getElementById('count__title').textContent = `コメント(${data.count})`;
                commentList.innerHTML += `
                <div class="comment">
                    <div class="comment__user">
                        <div class="user__img">
                            <img src="${ data.comment.user_profile }" alt="">
                        </div>
                        <p class="user__name">${ data.comment.user_name }</p>
                    </div>
                    <p class="comment__content">${data.comment.comment}</p>
                </div>
                `;
                document.getElementById('comment__textarea').value = '';
            }
        })
        .catch(error => {
            console.error(error);
        });
});
