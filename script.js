document.addEventListener("DOMContentLoaded", function() {
    // Add event listeners to each faculty card
    document.getElementById('faculty1').addEventListener('click', function() { showModules('faculty1'); });
    document.getElementById('faculty2').addEventListener('click', function() { showModules('faculty2'); });
    document.getElementById('faculty3').addEventListener('click', function() { showModules('faculty3'); });
    document.getElementById('faculty4').addEventListener('click', function() { showModules('faculty4'); });
    document.getElementById('faculty5').addEventListener('click', function() { showModules('faculty5'); });
});

function showModules(faculty) {
    const modulesSection = document.getElementById('faculty-modules');
    modulesSection.style.display = 'block';  // Show modules section

    // Reset content before adding new faculty details
    modulesSection.innerHTML = '';

    // Faculty module details
    const facultyModules = {
        "faculty1": {
            name: "Syed Shah Ali",
            role: "Senior Lecturer - Cyber Security",
            undergraduateModules: [
                { year: "Year 1", blocks: ["Block 1: Foundation of Computing and Cyber Security", "Block 2: Secure Coding"] },
                { year: "Year 2", blocks: ["Block 3: Penetration Testing", "Block 4: Industrial Cryptography"] },
                { year: "Year 3", blocks: ["Block 1: Cyber Physical Systems Security", "Block 3: Cyber Security and Social Responsibility"] }
            ],
            postgraduateModules: []
        },
        "faculty2": {
            name: "Sayed Ahmad",
            role: "Program Leader - Computer Science",
            undergraduateModules: [
                { year: "Year 1", blocks: ["Block 1: Database Design and Implementation", "Block 2: Fundamental Concepts of Computer Science"] },
                { year: "Year 2", blocks: ["Block 3: Web Application Development", "Block 4: Agile Development Team Project"] },
                { year: "Year 3", blocks: ["Block 1: Software Development: Methods and Standards", "Block 3: Development Project"] }
            ],
            postgraduateModules: []
        },
        "faculty3": {
            name: "Atheer Al-Mousa",
            role: "Postgraduate Lecturer - Cyber Security",
            undergraduateModules: [],
            postgraduateModules: [
                { blocks: ["Block 4: Human Factors, Research and Skills", "Block 5 & 6: PGT Project"] },
                { blocks: ["Block 1: Foundation of Cyber Security and Engineering", "Block 2: Cyber Threat Intelligence and Network Security", "Block 3: Malware Analysis, Penetration Testing and Incident Response"] }
            ]
        },
        "faculty4": {
            name: "Sumaiya Thaseen",
            role: "Senior Lecturer - Cyber Security",
            undergraduateModules: [
                { year: "Year 1", blocks: ["Block 3: Endpoint Security", "Block 4: Business Infrastructure and Security"] },
                { year: "Year 2", blocks: ["Block 1: Secure Scripting and Business Applications", "Block 2: Incident Response and Cyber Threat Intelligence"] },
                { year: "Year 3", blocks: ["Block 2: Malware and Attacker Techniques", "Block 4: Artificial Intelligence for Cyber Security"] }
            ],
            postgraduateModules: []
        },
        "faculty5": {
            name: "Muhammed Ghalib",
            role: "Senior Lecturer - Computer Science",
            undergraduateModules: [
                { year: "Year 1", blocks: ["Block 3: Computer Programming", "Block 4: Operating Systems and Networks"] },
                { year: "Year 2", blocks: ["Block 1: Object Oriented Design and Development", "Block 2: Data Structure and Algorithms"] },
                { year: "Year 3", blocks: ["Block 2: Big Data and Machine Learning", "Block 4: Functional Programming"] }
            ],
            postgraduateModules: [
                { blocks: ["Block 4: Digital Forensics with Legal, Ethical and Research Methods", "Block 5 & 6: PGT Project"] }
            ]
        }
    };

    const selectedFaculty = facultyModules[faculty];
    
    // Update faculty name and role
    modulesSection.innerHTML += `<h2>${selectedFaculty.name} - ${selectedFaculty.role}</h2>`;
    
    // Add Undergraduate Modules if available
    if (selectedFaculty.undergraduateModules.length > 0) {
        modulesSection.innerHTML += `<h3>Undergraduate Modules</h3><ul class="module-list">`;
        selectedFaculty.undergraduateModules.forEach(module => {
            modulesSection.innerHTML += `<li><strong>${module.year}</strong>:<br>${module.blocks.join('<br>')}</li>`;
        });
        modulesSection.innerHTML += `</ul>`;
    }

    // Add Postgraduate Modules if available
    if (selectedFaculty.postgraduateModules.length > 0) {
        modulesSection.innerHTML += `<h3>Postgraduate Modules</h3><ul class="module-list">`;
        selectedFaculty.postgraduateModules.forEach(module => {
            modulesSection.innerHTML += `<li>${module.blocks.join('<br>')}</li>`;
        });
        modulesSection.innerHTML += `</ul>`;
    }
}
// timer coundown for sesssion in admindashabord
document.addEventListener("DOMContentLoaded", function () {
    let timeLeft = sessionTimeout; // This will be set by PHP in the previous script

    function updateTimer() {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft % 60;
        document.getElementById("timer").innerText = `Session Timeout: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

        if (timeLeft > 0) {
            timeLeft--;
            setTimeout(updateTimer, 1000);
        } else {
            alert("Session expired! Redirecting to login...");
            window.location.href = "adminlogin.php"; // Redirect on timeout
        }
    }

    updateTimer(); // Start countdown
});



document.addEventListener("DOMContentLoaded", function () {
    const category = window.location.pathname.includes("undergraduate") ? "undergraduate" : "postgraduate";
    
    fetch(`get_programmes.php?category=${category}`)
        .then(response => response.json())
        .then(data => {
            const courseCardsContainer = document.querySelector('.course-cards');
            const courseTableRowsContainer = document.querySelector('.course-table');

            courseCardsContainer.innerHTML = '';
            const staticContent = courseTableRowsContainer.querySelectorAll('.header-row, .separator');
            courseTableRowsContainer.innerHTML = '';
            staticContent.forEach(element => courseTableRowsContainer.appendChild(element));

            if (data.error) {
                courseCardsContainer.innerHTML = `<p>Error: ${data.error}</p>`;
                courseTableRowsContainer.innerHTML += `<div class="row"><span colspan="3">Error: ${data.error}</span></div>`;
            } else if (data.message) {
                courseCardsContainer.innerHTML = `<p>${data.message}</p>`;
                courseTableRowsContainer.innerHTML += `<div class="row"><span colspan="3">${data.message}</span></div>`;
            } else if (Array.isArray(data) && data.length > 0) {
                data.forEach(programme => {
                    const courseCard = document.createElement('div');
                    courseCard.classList.add('card');
                    courseCard.innerHTML = `
                        <h2>${programme.name}</h2>
                        <p>${programme.description}</p>
                        <a href="${category === 'undergraduate' ? '' : 'P'}${programme.name.toLowerCase().replace(/\s+/g, '-')}.html" class="btn">View Course</a>
                    `;
                    courseCardsContainer.appendChild(courseCard);

                    const row = document.createElement('div');
                    row.classList.add('row');
                    row.innerHTML = `
                        <span>${programme.name}</span>
                        <span>${category === 'undergraduate' ? 'BSc (Hons)' : 'MSc (Hons)'}</span>
                        <span>Full-time</span>
                    `;
                    courseTableRowsContainer.appendChild(row);

                    if (data.indexOf(programme) < data.length - 1) {
                        const thinSeparator = document.createElement('div');
                        thinSeparator.classList.add('thin-separator');
                        courseTableRowsContainer.appendChild(thinSeparator);
                    }
                });
            } else {
                courseCardsContainer.innerHTML = `<p>No programmes available.</p>`;
                courseTableRowsContainer.innerHTML += `<div class="row"><span colspan="3">No programmes available.</span></div>`;
            }
        })
        .catch(error => {
            console.error('Error fetching programmes:', error);
            const courseCardsContainer = document.querySelector('.course-cards');
            const courseTableRowsContainer = document.querySelector('.course-table');
            courseCardsContainer.innerHTML = `<p>Error fetching programmes.</p>`;
            courseTableRowsContainer.innerHTML += `<div class="row"><span colspan="3">Error fetching programmes.</span></div>`;
        });
});
// Wait for the DOM to fully load before executing scripts
document.addEventListener("DOMContentLoaded", function () {
    // Hamburger menu toggle functionality
    const hamburger = document.querySelector(".hamburger");
    const sidebar = document.querySelector(".sidebar");
    const dashboard = document.querySelector(".admin-dashboard");
    const body = document.body;

    if (hamburger && sidebar && dashboard && body) {
        hamburger.addEventListener("click", function () {
            hamburger.classList.toggle("active"); // Transform hamburger to X
            sidebar.classList.toggle("active");   // Slide in/out sidebar
            dashboard.classList.toggle("active"); // Shift main content
            body.classList.toggle("sidebar-active"); // Toggle overlay
        });
    } else {
        console.error("Hamburger, sidebar, dashboard, or body elements not found:", {
            hamburger: !!hamburger,
            sidebar: !!sidebar,
            dashboard: !!dashboard,
            body: !!body
        });
    }

    // Session timeout logic
    const timerElement = document.getElementById("timer");
    if (timerElement) {
        let timeLeft = parseInt(timerElement.dataset.timeout); // Get timeout duration from data attribute

        function updateTimer() {
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            timerElement.innerText = `Session Timeout: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateTimer, 1000); // Update every second
            } else {
                alert("Session expired! Redirecting to login...");
                window.location.href = "adminlogin.php"; // Redirect on timeout
            }
        }

        updateTimer(); // Start the timer
    } else {
        console.error("Timer element not found.");
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Existing code

    // Modal code
    const modal = document.getElementById('emailModal');
    const sendMailButtons = document.querySelectorAll('.send-mail-btn');
    const recipientEmailInput = document.getElementById('modal_recipient_email');
    const recipientNameInput = document.getElementById('modal_recipient_name');
    const subjectInput = document.getElementById('modal_email_subject');
    const messageInput = document.getElementById('modal_email_message');

    sendMailButtons.forEach(button => {
        button.addEventListener('click', function() {
            const email = this.getAttribute('data-email');
            const name = this.getAttribute('data-name');
            recipientEmailInput.value = email;
            recipientNameInput.value = name;
            subjectInput.value = '';
            messageInput.value = '';
            modal.style.display = 'flex';
        });
    });

    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

function closeModal() {
    const modal = document.getElementById('emailModal');
    modal.style.display = 'none';
}

