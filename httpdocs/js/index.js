function doRedirect()
{
    location.href = "/html/success.html";
}


const form = document.querySelector('form');

const inputs = form.querySelectorAll('input, textarea');


form.addEventListener('submit', function(e) {
    e.preventDefault();     
    const formData = new FormData(form);

    fetch('http://localhost/',
    {
        method: 'POST',
        body: formData
    })
    .then(response => 
    {
        if (response.status === 200) 
        {
            response.json().then(data => 
            {
                console.log(data.msg);

                for (let field in data.errors)
                {
                    if(data.errors[field])
                    {
                        const fieldName = field.replace('_err', '');
                        const fieldElement = document.getElementById(fieldName);
                        fieldElement.classList.remove('campo-errore');
                        fieldElement.classList.add('input');
                        const errDiv = document.getElementById(`${fieldName}-err`);
                        errDiv.style.display = 'none';
                    }
                }
                const successMessageContainer = document.getElementById('success-message-container');
                successMessageContainer.querySelector('span').textContent = data.msg;
                successMessageContainer.style.display = 'block';
                setTimeout(doRedirect, 5000);
            });
        }
        else
        {
            response.json().then(data => {
                console.error(data.msg);
                for (let errorKey in data.errors) {
                    if (data.errors[errorKey]) 
                    {
                        const fieldName = errorKey.replace('_err', '');
                        const errDiv = document.getElementById(`${fieldName}-err`);
                        const fieldElement = document.getElementById(fieldName);

                        fieldElement.classList.remove('input');
                        fieldElement.classList.add('campo-errore');
                        errDiv.style.display = 'block';
                        errDiv.querySelector('.errore').textContent = data.errors[errorKey];                   
                        
                        fieldElement.addEventListener('input', function() {
                            fieldElement.classList.remove('campo-errore');
                            fieldElement.classList.add('input');
                            errDiv.style.display = 'none';
                        }, { once: true });
                    }
                }
            });
        }
    });
});