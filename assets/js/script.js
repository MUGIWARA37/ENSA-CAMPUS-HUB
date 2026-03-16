let container = document.getElementById('container')

toggle = () => {
    container.classList.toggle('sign-in')
    container.classList.toggle('sign-up')
}

setTimeout(() => {
    container.classList.add('sign-in')
}, 200)

// Register user function
function registerUser() {
    // Get form values
    const firstName = document.querySelector('.form.sign-up .input-group:nth-child(1) input').value;
    const lastName = document.querySelector('.form.sign-up .input-group:nth-child(2) input').value;
    const email = document.querySelector('.form.sign-up .input-group:nth-child(3) input').value;
    const cin = document.querySelector('.form.sign-up .input-group:nth-child(4) input').value;
    const sector = document.querySelector('.form.sign-up #sector').value;
    const password = document.querySelector('.form.sign-up .input-group:nth-child(6) input').value;
    const confirmPassword = document.querySelector('.form.sign-up .input-group:nth-child(7) input').value;
    
    // Basic validation
    if (!firstName || !lastName || !email || !cin || !sector || !password || !confirmPassword) {
        alert('Please fill in all fields');
        return;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }
    
    // Save user data to localStorage
    const userData = {
        firstName: firstName,
        lastName: lastName,
        email: email,
        cin: cin,
        sector: sector,
        password: password
    };
    
    localStorage.setItem('userData', JSON.stringify(userData));
    localStorage.setItem('currentUser', JSON.stringify({firstName: firstName, lastName: lastName}));
    
    alert('Registration successful! Redirecting to home page...');
    window.location.href = 'home.html';
}

// Login user function
function loginUser() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    // Get stored user data
    const storedData = localStorage.getItem('userData');
    
    if (!storedData) {
        alert('No user found. Please register first.');
        return;
    }
    
    const userData = JSON.parse(storedData);
    
    if (userData.email === email && userData.password === password) {
        // Save current user
        localStorage.setItem('currentUser', JSON.stringify({
            firstName: userData.firstName,
            lastName: userData.lastName
        }));
        
        alert('Login successful! Redirecting to home page...');
        window.location.href = 'home.html';
    } else {
        alert('Invalid email or password');
    }
}
